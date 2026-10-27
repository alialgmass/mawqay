<?php

namespace Modules\Shipping\Contracts;

use Modules\Sales\Models\Order;
use Modules\Shipping\Models\Shipment;

/**
 * A way of handing an Order to a courier. Only the manual one exists; a gateway can be plugged in later.
 */
interface ShippingProvider
{
    /**
     * @param  array{courier_name: string, tracking_number?: string|null, tracking_url?: string|null, note?: string|null}  $details
     */
    public function create(Order $order, array $details): Shipment;

    /**
     * @return array{courier_name: string, tracking_number: string|null, tracking_url: string|null, shipped_at: string, delivered_at: string|null}|null
     */
    public function track(Order $order): ?array;
}
