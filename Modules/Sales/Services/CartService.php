<?php

namespace Modules\Sales\Services;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\Customer;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\Variant;
use Modules\Core\Money\Money;
use Modules\Sales\Models\Cart;
use Modules\Sales\Models\CartItem;
use Modules\Tenancy\Models\Store;

/**
 * The visitor's Cart in the current Store: a signed-in Customer's, or a
 * guest's identified by a token in their session. A Cart holds no Stock.
 */
class CartService
{
    private const TOKEN_KEY = 'cart.token';

    /**
     * The visitor's Cart, if they have one.
     */
    public function current(): ?Cart
    {
        $customer = $this->customer();

        if ($customer !== null) {
            return Cart::query()->where('customer_id', $customer->getKey())->first();
        }

        $token = session(self::TOKEN_KEY);

        return $token === null ? null : Cart::query()->where('guest_token', $token)->first();
    }

    public function count(): int
    {
        return (int) ($this->current()?->items()->sum('quantity') ?? 0);
    }

    public function add(Variant $variant, int $quantity): CartItem
    {
        $this->assertCanBuy($variant);

        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => __('sales::cart.errors.quantity')]);
        }

        return DB::transaction(function () use ($variant, $quantity): CartItem {
            $cart = $this->currentOrCreate();
            $item = $cart->items()->where('variant_id', $variant->id)->first();
            $wanted = ($item?->quantity ?? 0) + $quantity;

            $this->assertAvailable($variant, $wanted);

            if ($item === null) {
                return $cart->items()->create([
                    'variant_id' => $variant->id,
                    'quantity' => $wanted,
                    'price_when_added' => $variant->price()->piasters(),
                ]);
            }

            $item->update(['quantity' => $wanted]);

            return $item;
        });
    }

    public function setQuantity(CartItem $item, int $quantity): CartItem
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => __('sales::cart.errors.quantity')]);
        }

        $this->assertCanBuy($item->variant);
        $this->assertAvailable($item->variant, $quantity);
        $item->update(['quantity' => $quantity]);

        return $item;
    }

    public function remove(CartItem $item): void
    {
        $item->delete();
    }

    /**
     * Accept the prices shown today for every line, after the Customer has seen the change.
     */
    public function acceptPrices(Cart $cart): void
    {
        foreach ($cart->items()->with('variant.product')->get() as $item) {
            $item->update(['price_when_added' => $item->variant->price()->piasters()]);
        }
    }

    /**
     * The Cart as a page needs it, with flags for lines that need attention.
     *
     * @return array{lines: list<array<string, mixed>>, subtotal: int, count: int, canCheckout: bool}
     */
    public function summary(?Cart $cart = null): array
    {
        $cart ??= $this->current();
        $items = $cart?->items()->with(['variant.product.media'])->get() ?? collect();

        $lines = [];
        $subtotal = 0;

        foreach ($items as $item) {
            $variant = $item->variant;
            $buyable = $this->isBuyable($variant);
            $available = $buyable ? $variant->available() : 0;
            $unitPrice = $variant->price()->piasters();
            $lineTotal = $unitPrice * $item->quantity;
            $subtotal += $lineTotal;

            $lines[] = [
                'id' => $item->id,
                'variantId' => $variant->id,
                'name' => $variant->product->name,
                'sku' => $variant->sku,
                'size' => $variant->size,
                'color' => $variant->color,
                'imageUrl' => $variant->product->media->first()?->url('thumb'),
                'productUrl' => route('storefront.product', ['store' => Store::current()->slug, 'product' => $variant->product->slug]),
                'quantity' => $item->quantity,
                'unitPrice' => $unitPrice,
                'lineTotal' => $lineTotal,
                'available' => $available,
                'unavailable' => ! $buyable || $item->quantity > $available,
                'priceChanged' => $unitPrice !== $item->price_when_added,
                'previousPrice' => $item->price_when_added,
            ];
        }

        $needsAttention = collect($lines)->contains(fn (array $line): bool => $line['unavailable'] || $line['priceChanged']);

        return [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'count' => (int) $items->sum('quantity'),
            'canCheckout' => $lines !== [] && ! $needsAttention && Store::current()->canTrade(),
        ];
    }

    /**
     * Move a guest's Cart into a Customer's: quantities add up, capped at
     * what is available, and the guest Cart is removed.
     */
    public function mergeGuestCartInto(Customer $customer): void
    {
        $token = session(self::TOKEN_KEY);

        if ($token === null) {
            return;
        }

        DB::transaction(function () use ($customer, $token): void {
            $guest = Cart::query()->where('guest_token', $token)->first();

            if ($guest === null) {
                return;
            }

            $mine = Cart::query()->where('customer_id', $customer->getKey())->first();

            $mine ??= Cart::query()->create(['customer_id' => $customer->getKey()]);

            foreach ($guest->items()->with('variant')->get() as $item) {
                if (! $this->isBuyable($item->variant)) {
                    continue;
                }

                $existing = $mine->items()->where('variant_id', $item->variant_id)->first();
                $quantity = min(($existing?->quantity ?? 0) + $item->quantity, $item->variant->available());

                if ($quantity < 1) {
                    continue;
                }

                if ($existing === null) {
                    $mine->items()->create(['variant_id' => $item->variant_id, 'quantity' => $quantity, 'price_when_added' => $item->price_when_added]);
                } else {
                    $existing->update(['quantity' => $quantity]);
                }
            }

            $guest->delete();
        });

        session()->forget(self::TOKEN_KEY);
    }

    private function currentOrCreate(): Cart
    {
        $existing = $this->current();

        if ($existing !== null) {
            return $existing;
        }

        $customer = $this->customer();

        if ($customer !== null) {
            return $this->createOrFind(['customer_id' => $customer->getKey()]);
        }

        $token = session(self::TOKEN_KEY) ?? Str::random(40);
        session([self::TOKEN_KEY => $token]);

        return $this->createOrFind(['guest_token' => $token]);
    }

    /**
     * Two first additions at the same moment both try to create the Cart; the loser uses the winner's.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createOrFind(array $attributes): Cart
    {
        try {
            return Cart::query()->create($attributes);
        } catch (UniqueConstraintViolationException) {
            return Cart::query()->where($attributes)->firstOrFail();
        }
    }

    private function customer(): ?Customer
    {
        $customer = Auth::guard('customer')->user();

        return $customer instanceof Customer && $customer->store_id === Store::current()?->id ? $customer : null;
    }

    private function isBuyable(Variant $variant): bool
    {
        return Product::query()->published()->whereKey($variant->product_id)->exists();
    }

    private function assertCanBuy(Variant $variant): void
    {
        if (! Store::current()->canTrade()) {
            throw ValidationException::withMessages(['variant' => __('sales::cart.errors.store_paused')]);
        }

        if (! $this->isBuyable($variant)) {
            throw ValidationException::withMessages(['variant' => __('sales::cart.errors.not_for_sale')]);
        }
    }

    private function assertAvailable(Variant $variant, int $quantity): void
    {
        if ($quantity > $variant->available()) {
            throw ValidationException::withMessages(['quantity' => __('sales::cart.errors.not_enough_stock', ['available' => $variant->available()])]);
        }
    }

    public static function money(int $piasters): string
    {
        return Money::fromPiasters($piasters)->format();
    }
}
