<?php

namespace Modules\Sales\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\Address;
use Modules\Auth\Models\Customer;
use Modules\Catalog\Exceptions\InsufficientStock;
use Modules\Catalog\Services\StockService;
use Modules\Core\Support\PlatformSettings;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Events\OrderPlaced;
use Modules\Sales\Exceptions\OrderCannotBePlaced;
use Modules\Sales\Models\Order;
use Modules\Sales\Services\CartService;
use Modules\Sales\Services\OrderNumberGenerator;
use Modules\Sales\Services\ShippingFeeResolver;
use Modules\Sales\Support\CommissionCalculator;
use Modules\Tenancy\Models\Store;

/**
 * Turns the Customer's Cart into an Order awaiting payment, in one
 * transaction: nothing is created or held unless everything succeeds.
 */
class PlaceOrder
{
    public function __construct(
        private readonly CartService $carts,
        private readonly ShippingFeeResolver $shipping,
        private readonly CommissionCalculator $commission,
        private readonly OrderNumberGenerator $numbers,
        private readonly StockService $stock,
        private readonly PlatformSettings $settings,
    ) {}

    /**
     * @throws OrderCannotBePlaced
     */
    public function __invoke(Customer $customer, Address $address, string $paymentMethod): Order
    {
        $store = Store::current();

        $order = DB::transaction(function () use ($store, $customer, $address, $paymentMethod): Order {
            $this->assertCanPlace($store, $customer, $address, $paymentMethod);

            $cart = $this->carts->current();
            $summary = $this->carts->summary($cart);
            $this->assertCartIsReady($summary);

            $fee = $this->shipping->resolve($address->governorate);

            if ($fee === null) {
                throw new OrderCannotBePlaced(__('sales::checkout.errors.no_delivery'));
            }

            $rate = $this->commission->rateFor($store);
            $order = Order::query()->create([
                'number' => $this->numbers->next($store),
                'customer_id' => $customer->id,
                'status' => OrderStatus::PendingPayment,
                'subtotal' => $summary['subtotal'],
                'shipping_fee' => $fee,
                'total' => $summary['subtotal'] + $fee,
                'currency' => config('sales.currency'),
                'payment_method' => $paymentMethod,
                'shipping_address' => $this->addressSnapshot($address),
                'customer_snapshot' => ['name' => $customer->name, 'email' => $customer->email, 'phone' => $customer->phone],
                'expires_at' => now()->addHours($this->settings->stockReservationHours()),
            ]);

            foreach ($cart->items()->with('variant.product')->get() as $item) {
                $variant = $item->variant;
                $unitPrice = $variant->price()->piasters();
                $lineTotal = $unitPrice * $item->quantity;

                $order->items()->create([
                    'variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'sku' => $variant->sku,
                    'size' => $variant->size,
                    'color' => $variant->color,
                    'unit_price' => $unitPrice,
                    'quantity' => $item->quantity,
                    'line_total' => $lineTotal,
                    'commission_rate' => $rate,
                    'commission_amount' => CommissionCalculator::amount($lineTotal, $rate),
                ]);

                try {
                    $this->stock->reserve($variant, $item->quantity);
                } catch (InsufficientStock) {
                    throw new OrderCannotBePlaced(__('sales::checkout.errors.out_of_stock'), [$variant->product->name]);
                }
            }

            $order->statusLogs()->create([
                'from_status' => null,
                'to_status' => OrderStatus::PendingPayment->value,
                'actor_type' => 'customer',
                'actor_id' => $customer->id,
                'note' => null,
            ]);

            $cart->delete();

            return $order;
        });

        OrderPlaced::dispatch($order);

        return $order;
    }

    private function assertCanPlace(Store $store, Customer $customer, Address $address, string $paymentMethod): void
    {
        if (! $store->canTrade()) {
            throw new OrderCannotBePlaced(__('sales::cart.errors.store_paused'));
        }

        if ($address->customer_id !== $customer->id) {
            throw new OrderCannotBePlaced(__('sales::checkout.errors.address'));
        }

        if (! array_key_exists($paymentMethod, config('sales.payment_methods'))) {
            throw new OrderCannotBePlaced(__('sales::checkout.errors.payment_method'));
        }
    }

    /**
     * @param  array{lines: list<array<string, mixed>>, canCheckout: bool}  $summary
     */
    private function assertCartIsReady(array $summary): void
    {
        if ($summary['lines'] === []) {
            throw new OrderCannotBePlaced(__('sales::checkout.errors.empty'));
        }

        $problems = collect($summary['lines'])->filter(fn (array $line): bool => $line['unavailable'] || $line['priceChanged']);

        if ($problems->isNotEmpty()) {
            $names = $problems->pluck('name')->unique()->values()->all();
            $reason = $problems->contains(fn (array $line): bool => $line['unavailable'])
                ? __('sales::checkout.errors.out_of_stock')
                : __('sales::checkout.errors.price_changed');

            throw new OrderCannotBePlaced($reason, $names);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function addressSnapshot(Address $address): array
    {
        return [
            'label' => $address->label,
            'recipient_name' => $address->recipient_name,
            'phone' => $address->phone,
            'governorate' => $address->governorate,
            'governorate_name' => $address->governorateName(),
            'city' => $address->city,
            'address_line' => $address->address_line,
            'notes' => $address->notes,
        ];
    }
}
