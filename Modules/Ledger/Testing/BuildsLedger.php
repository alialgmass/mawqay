<?php

namespace Modules\Ledger\Testing;

use Modules\Catalog\Models\Variant;
use Modules\Core\Support\StoreContext;
use Modules\Payments\Enums\PaymentStatus;
use Modules\Payments\Events\PaymentApproved;
use Modules\Payments\Models\Payment;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Models\Order;
use Modules\Sales\Support\CommissionCalculator;
use Modules\Sales\Testing\BuildsShop;
use Modules\Tenancy\Models\Store;

/**
 * Builders for Ledger tests: Orders with chosen prices and rates, and their approved Payments.
 */
trait BuildsLedger
{
    use BuildsShop;

    /** @var array<int, Variant> one Variant per Store is enough for Order lines */
    private array $ledgerVariants = [];

    /**
     * An Order with the given lines, each [unit price in piasters, quantity, rate in percent].
     *
     * @param  list<array{0: int, 1: int, 2: string|int|float}>  $lines
     * @param  array<string, mixed>  $attributes
     */
    protected function orderWith(Store $store, array $lines, int $shipping = 0, OrderStatus $status = OrderStatus::Paid, array $attributes = []): Order
    {
        $variant = $this->ledgerVariants[$store->id] ??= $this->variant($store);
        $subtotal = array_sum(array_map(fn (array $line): int => $line[0] * $line[1], $lines));

        return app(StoreContext::class)->runAs($store->id, function () use ($store, $lines, $shipping, $status, $attributes, $variant, $subtotal): Order {
            $order = Order::factory()->status($status)->create([
                'store_id' => $store->id,
                'subtotal' => $subtotal,
                'shipping_fee' => $shipping,
                'total' => $subtotal + $shipping,
                ...$attributes,
            ]);

            foreach ($lines as [$price, $quantity, $rate]) {
                $order->items()->create([
                    'variant_id' => $variant->id, 'product_name' => 'Abaya', 'sku' => $variant->sku, 'size' => 'M', 'color' => 'Black',
                    'unit_price' => $price, 'quantity' => $quantity, 'line_total' => $price * $quantity,
                    'commission_rate' => number_format((float) $rate, 2, '.', ''),
                    'commission_amount' => CommissionCalculator::amount($price * $quantity, $rate),
                ]);
            }

            return $order;
        });
    }

    /**
     * Approves a Payment for the Order as Payments does, announcing it so the Ledger posts it.
     */
    protected function approvedPaymentFor(Order $order): Payment
    {
        $payment = app(StoreContext::class)->runAs($order->store_id, fn (): Payment => Payment::factory()->create([
            'store_id' => $order->store_id,
            'order_id' => $order->id,
            'amount' => $order->total,
            'status' => PaymentStatus::Approved,
        ]));

        PaymentApproved::dispatch($payment, $order);

        return $payment;
    }

    /**
     * A paid Order whose money has been posted.
     *
     * @param  list<array{0: int, 1: int, 2: string|int|float}>  $lines
     */
    protected function postedOrder(Store $store, array $lines = [[100000, 1, 10]], int $shipping = 0, OrderStatus $status = OrderStatus::Paid): Order
    {
        $order = $this->orderWith($store, $lines, $shipping, $status);
        $this->approvedPaymentFor($order);

        return $order;
    }

    protected function deliveredDaysAgo(Order $order, int $days): Order
    {
        app(StoreContext::class)->runAs($order->store_id, fn () => Order::query()->whereKey($order->id)->update(['status' => OrderStatus::Delivered->value, 'delivered_at' => now()->subDays($days)]));

        return $order->refresh();
    }
}
