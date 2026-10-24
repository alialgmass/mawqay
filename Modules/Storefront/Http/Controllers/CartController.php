<?php

namespace Modules\Storefront\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Models\Variant;
use Modules\Core\Money\Money;
use Modules\Sales\Models\CartItem;
use Modules\Sales\Services\CartService;
use Modules\Storefront\Pages\CartPageData;
use Modules\Storefront\Support\StorefrontPresenter;
use Modules\Storefront\Support\ThemeRenderer;
use Modules\Tenancy\Models\Store;
use Symfony\Component\HttpFoundation\Response;

class CartController
{
    public function __construct(
        private readonly CartService $carts,
        private readonly StorefrontPresenter $presenter,
        private readonly ThemeRenderer $renderer,
    ) {}

    public function show(): Response
    {
        $summary = $this->carts->summary();

        return $this->renderer->render(new CartPageData(
            $this->presenter->shell(),
            $this->presenter->seo(__('storefront::pages.cart').' | '.Store::current()->name),
            items: collect($summary['lines'])->map(fn (array $line): array => $this->line($line))->all(),
            total: Money::fromPiasters($summary['subtotal'])->format(),
            canCheckout: $summary['canCheckout'],
            hasChanges: collect($summary['lines'])->contains(fn (array $line): bool => $line['unavailable'] || $line['priceChanged']),
            storeOpen: Store::current()->canTrade(),
        ));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
        ]);

        $variant = Variant::query()->find($data['variant_id']);

        if ($variant === null) {
            throw ValidationException::withMessages(['variant_id' => __('sales::cart.errors.not_for_sale')]);
        }

        $this->carts->add($variant, (int) ($data['quantity'] ?? 1));

        return $this->respond($request, __('sales::cart.added'));
    }

    public function update(Request $request, string $store, int $item): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1']]);

        $this->carts->setQuantity($this->ownItem($item), (int) $data['quantity']);

        return $this->respond($request, __('sales::cart.updated'));
    }

    public function destroy(Request $request, string $store, int $item): JsonResponse|RedirectResponse
    {
        $this->carts->remove($this->ownItem($item));

        return $this->respond($request, __('sales::cart.removed'));
    }

    public function acceptPrices(Request $request): JsonResponse|RedirectResponse
    {
        $cart = $this->carts->current();

        if ($cart !== null) {
            $this->carts->acceptPrices($cart);
        }

        return $this->respond($request, __('sales::cart.prices_accepted'));
    }

    private function ownItem(int $id): CartItem
    {
        return $this->carts->current()?->items()->findOrFail($id) ?? abort(404);
    }

    private function respond(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['count' => $this->carts->count()]);
        }

        return back()->with('status', $message);
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    private function line(array $line): array
    {
        return [
            ...collect($line)->only(['id', 'variantId', 'name', 'sku', 'size', 'color', 'imageUrl', 'productUrl', 'quantity', 'available', 'unavailable', 'priceChanged'])->all(),
            'unitPrice' => Money::fromPiasters($line['unitPrice'])->format(),
            'lineTotal' => Money::fromPiasters($line['lineTotal'])->format(),
            'previousPrice' => $line['priceChanged'] ? Money::fromPiasters($line['previousPrice'])->format() : null,
        ];
    }
}
