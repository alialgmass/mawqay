<?php

namespace Modules\Storefront\Support;

use Modules\Core\Money\Money;
use Modules\Payments\Support\PaymentBlock;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Models\Order;
use Modules\Sales\Models\OrderItem;
use Modules\Tenancy\Models\Store;

/**
 * Orders as the Storefront pages receive them: plain values, prices already formatted.
 */
class OrderPresenter
{
    public function __construct(private readonly PaymentBlock $payments) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(Order $order): array
    {
        $slug = Store::current()->slug;

        return [
            'number' => $order->number,
            'status' => $order->status->value,
            'statusLabel' => __('sales::orders.statuses.'.$order->status->value),
            'total' => $order->total()->format(),
            'placedAt' => $order->created_at->toDateString(),
            'url' => url("/{$slug}/orders/{$order->number}"),
            'canCancel' => $order->status === OrderStatus::PendingPayment,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Order $order): array
    {
        $slug = Store::current()->slug;

        return [
            ...$this->summary($order),
            'expiresAt' => $order->status === OrderStatus::PendingPayment ? $order->expires_at?->toIso8601String() : null,
            'subtotal' => $order->subtotal()->format(),
            'shippingFee' => $order->shippingFee()->format(),
            'paymentMethod' => $order->payment_method,
            'paymentMethodLabel' => __('sales::orders.payment_methods.'.$order->payment_method),
            'address' => $order->shipping_address,
            'items' => $order->items->map(fn (OrderItem $item): array => [
                'name' => $item->product_name,
                'sku' => $item->sku,
                'size' => $item->size,
                'color' => $item->color,
                'quantity' => $item->quantity,
                'unitPrice' => Money::fromPiasters($item->unit_price)->format(),
                'lineTotal' => $item->lineTotal()->format(),
            ])->values()->all(),
            'cancelUrl' => url("/{$slug}/orders/{$order->number}/cancel"),
            'payment' => $this->payments->for($order, url("/{$slug}/orders/{$order->number}/receipt")),
        ];
    }
}
