<?php

namespace Modules\Shipping\Carriers;

use Modules\Sales\Models\Order;
use Modules\Shipping\Contracts\ShippingProvider;
use Modules\Shipping\Models\Shipment;

/**
 * The Vendor ships by hand and records who carried it; tracking is just what they recorded.
 */
class ManualShippingProvider implements ShippingProvider
{
    public function create(Order $order, array $details): Shipment
    {
        return Shipment::query()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'store_id' => $order->store_id,
                'courier_name' => $details['courier_name'],
                'tracking_number' => $details['tracking_number'] ?? null,
                'tracking_url' => $details['tracking_url'] ?? null,
                'note' => $details['note'] ?? null,
                'shipped_at' => now(),
            ],
        );
    }

    public function track(Order $order): ?array
    {
        $shipment = Shipment::query()->where('order_id', $order->id)->first();

        return $shipment === null ? null : [
            'courier_name' => $shipment->courier_name,
            'tracking_number' => $shipment->tracking_number,
            'tracking_url' => $shipment->tracking_url,
            'shipped_at' => $shipment->shipped_at->toIso8601String(),
            'delivered_at' => $shipment->delivered_at?->toIso8601String(),
        ];
    }
}
