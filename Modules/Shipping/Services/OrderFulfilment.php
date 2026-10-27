<?php

namespace Modules\Shipping\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Core\Support\StoreContext;
use Modules\Sales\Contracts\Fulfilment;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Events\OrderDelivered;
use Modules\Sales\Models\Order;
use Modules\Sales\Services\OrderStateMachine;
use Modules\Sales\Support\OrderNotifier;
use Modules\Shipping\Contracts\ShippingProvider;
use Modules\Shipping\Models\Shipment;

/**
 * Every fulfilment step goes through the state machine, so a stale view or a second click is refused.
 * Whichever of the Vendor marking delivered or the Customer confirming receipt comes first stamps the delivery time.
 */
class OrderFulfilment implements Fulfilment
{
    public function __construct(
        private readonly OrderStateMachine $machine,
        private readonly ShippingProvider $carrier,
        private readonly OrderNotifier $notifier,
        private readonly StoreContext $context,
    ) {}

    public function startProcessing(Order $order, string $actorType, ?int $actorId): Order
    {
        return $this->context->runAs($order->store_id, fn (): Order => $this->machine->transition($order, OrderStatus::Processing, $actorType, $actorId, null, [OrderStatus::Paid]));
    }

    public function markShipped(Order $order, array $details, string $actorType, ?int $actorId): Order
    {
        $details = Validator::make($details, [
            'courier_name' => ['required', 'string', 'max:255'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'tracking_url' => ['nullable', 'url', 'max:2048'],
            'note' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $this->context->runAs($order->store_id, function () use ($order, $details, $actorType, $actorId): void {
            DB::transaction(function () use ($order, $details, $actorType, $actorId): void {
                $this->machine->transition($order, OrderStatus::Shipped, $actorType, $actorId, null, [OrderStatus::Processing]);
                $this->carrier->create($order, $details);
            });

            $this->notifier->customer($order, 'shipped', ['courier' => $details['courier_name']]);
        });

        return $order;
    }

    public function markDelivered(Order $order, string $actorType, ?int $actorId): Order
    {
        $this->context->runAs($order->store_id, function () use ($order, $actorType, $actorId): void {
            DB::transaction(function () use ($order, $actorType, $actorId): void {
                $this->machine->transition($order, OrderStatus::Delivered, $actorType, $actorId, null, [OrderStatus::Shipped]);

                Shipment::query()->where('order_id', $order->id)->whereNull('delivered_at')->update(['delivered_at' => $order->delivered_at]);
            });

            OrderDelivered::dispatch($order);
            $this->notifier->customer($order, 'delivered');
        });

        return $order;
    }
}
