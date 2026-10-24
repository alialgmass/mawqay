<?php

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Exceptions\InvalidOrderTransition;
use Modules\Sales\Models\Order;

/**
 * The only way an Order changes status: the move must be allowed and is
 * written to the log in the same transaction.
 */
class OrderStateMachine
{
    /**
     * @param  string  $actorType  customer, admin, vendor or system
     *
     * @throws InvalidOrderTransition
     */
    public function transition(Order $order, OrderStatus $to, string $actorType, ?int $actorId = null, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $to, $actorType, $actorId, $note): Order {
            // Re-read under lock so two actors cannot both move the same Order.
            $current = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if (! $current->status->canMoveTo($to)) {
                throw new InvalidOrderTransition($current->status, $to);
            }

            $from = $current->status;
            $current->status = $to;

            if ($to === OrderStatus::Cancelled) {
                $current->cancelled_at = now();
            }

            $current->save();

            $current->statusLogs()->create([
                'from_status' => $from->value,
                'to_status' => $to->value,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'note' => $note,
            ]);

            $order->setRawAttributes($current->getAttributes(), true);

            return $order;
        });
    }
}
