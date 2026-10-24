<?php

namespace Modules\Sales\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Services\StockService;
use Modules\Core\Support\StoreContext;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Events\OrderCancelled;
use Modules\Sales\Models\Order;
use Modules\Sales\Services\OrderStateMachine;

/**
 * Cancels an unpaid Order and gives its held Stock back. Runs inside the
 * Order's own Store, whoever asks.
 */
class CancelOrder
{
    public function __construct(
        private readonly OrderStateMachine $machine,
        private readonly StockService $stock,
        private readonly StoreContext $context,
    ) {}

    /**
     * @param  string  $actorType  customer, admin or system
     */
    public function __invoke(Order $order, string $actorType, ?int $actorId = null, ?string $reason = null): Order
    {
        return $this->context->runAs($order->store_id, function () use ($order, $actorType, $actorId, $reason): Order {
            DB::transaction(function () use ($order, $actorType, $actorId, $reason): void {
                $this->machine->transition($order, OrderStatus::Cancelled, $actorType, $actorId, $reason);

                foreach ($order->items()->with('variant')->get() as $item) {
                    $this->stock->release($item->variant, $item->quantity);
                }
            });

            OrderCancelled::dispatch($order, $actorType, $reason);

            return $order;
        });
    }
}
