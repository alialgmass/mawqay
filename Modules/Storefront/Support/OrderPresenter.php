<?php

namespace Modules\Storefront\Support;

use Modules\Core\Money\Money;
use Modules\Core\Support\PlatformSettings;
use Modules\Feedback\Models\Survey;
use Modules\Feedback\Services\SurveyLinks;
use Modules\Payments\Support\PaymentBlock;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Models\Order;
use Modules\Sales\Models\OrderItem;
use Modules\Sales\Models\OrderStatusLog;
use Modules\Shipping\Models\Shipment;
use Modules\Tenancy\Models\Store;

/**
 * Orders as the Storefront pages receive them: plain values, prices already formatted.
 */
class OrderPresenter
{
    public function __construct(
        private readonly PaymentBlock $payments,
        private readonly SurveyLinks $surveyLinks,
        private readonly PlatformSettings $settings,
    ) {}

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
            'payment' => $this->payments->for($order, url("/{$slug}/orders/{$order->number}/receipt"), url("/{$slug}/orders/{$order->number}/pay-online")),
            'timeline' => $order->statusLogs->map(fn (OrderStatusLog $log): array => [
                'status' => $log->to_status->value,
                'label' => __('sales::orders.statuses.'.$log->to_status->value),
                'at' => $log->created_at->toIso8601String(),
            ])->values()->all(),
            'shipment' => $this->shipment($order),
            'survey' => $this->survey($order),
            'canConfirmReceipt' => $order->status === OrderStatus::Shipped,
            'confirmReceiptUrl' => url("/{$slug}/orders/{$order->number}/received"),
            'storeContact' => [
                'email' => Store::current()->contact_email,
                'phone' => Store::current()->contact_phone,
                'whatsapp' => Store::current()->contact_whatsapp,
            ],
        ];
    }

    /**
     * A Survey the Customer has not answered yet, with its signed link; null when there is none or it is answered.
     *
     * @return array{pending: bool, url: string}|null
     */
    private function survey(Order $order): ?array
    {
        $due = $order->delivered_at !== null && $order->delivered_at->lessThanOrEqualTo(now()->subDays($this->settings->surveyDelayDays()));

        if (! $due || in_array($order->status, [OrderStatus::Refunded, OrderStatus::Cancelled], true)) {
            return null;
        }

        $survey = Survey::query()->where('order_id', $order->id)->whereNull('submitted_at')->first();

        return $survey === null ? null : ['pending' => true, 'url' => $this->surveyLinks->show($survey)];
    }

    /**
     * @return array{courier: string, trackingNumber: string|null, trackingUrl: string|null, shippedAt: string, deliveredAt: string|null}|null
     */
    private function shipment(Order $order): ?array
    {
        $shipment = Shipment::query()->where('order_id', $order->id)->first();

        return $shipment === null ? null : [
            'courier' => $shipment->courier_name,
            'trackingNumber' => $shipment->tracking_number,
            'trackingUrl' => $shipment->tracking_url,
            'shippedAt' => $shipment->shipped_at->toIso8601String(),
            'deliveredAt' => $shipment->delivered_at?->toIso8601String(),
        ];
    }
}
