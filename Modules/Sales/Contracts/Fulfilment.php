<?php

namespace Modules\Sales\Contracts;

use Modules\Sales\Exceptions\InvalidOrderTransition;
use Modules\Sales\Models\Order;

/**
 * Moving a paid Order through preparation, shipping and delivery. Implemented by Shipping,
 * which owns the Shipment record; Sales only asks.
 */
interface Fulfilment
{
    /**
     * @throws InvalidOrderTransition
     */
    public function startProcessing(Order $order, string $actorType, ?int $actorId): Order;

    /**
     * @param  array{courier_name: string, tracking_number?: string|null, tracking_url?: string|null, note?: string|null}  $details
     *
     * @throws InvalidOrderTransition
     */
    public function markShipped(Order $order, array $details, string $actorType, ?int $actorId): Order;

    /**
     * Vendor marks it delivered, or the Customer confirms receipt. The first one starts the Return Window.
     *
     * @throws InvalidOrderTransition
     */
    public function markDelivered(Order $order, string $actorType, ?int $actorId): Order;
}
