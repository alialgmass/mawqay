<?php

namespace Modules\Sales\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Core\Support\Audit;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Events\OrderDelivered;
use Modules\Sales\Exceptions\InvalidOrderTransition;
use Modules\Sales\Exceptions\RefundNotAllowed;
use Modules\Sales\Models\Order;
use Modules\Sales\Services\OrderStateMachine;

/**
 * A Platform Admin repairs a stuck Order. The move must still be one the Order may make, a reason
 * is mandatory and the override is audited. It never makes money: Paid and Refunded have their own screens.
 */
class ForceOrderStatus
{
    public function __construct(private readonly OrderStateMachine $machine) {}

    /**
     * @throws InvalidOrderTransition
     * @throws RefundNotAllowed
     */
    public function __invoke(Order $order, OrderStatus $to, string $reason, ?int $actorId): Order
    {
        if (blank($reason)) {
            throw new RefundNotAllowed('A reason is required to force a status change.');
        }

        if (in_array($to, [OrderStatus::Paid, OrderStatus::Refunded, OrderStatus::Completed], true)) {
            throw new RefundNotAllowed("Use Payment Review, Refund or the funds release instead of forcing {$to->value}.");
        }

        $from = $order->status;

        DB::transaction(function () use ($order, $to, $reason, $actorId, $from): void {
            $this->machine->transition($order, $to, 'admin', $actorId, 'Forced: '.$reason, [$from]);

            Audit::record('order_status_forced', $order, ['status' => $from->value], ['status' => $to->value], $reason, $order->store_id, $actorId);
        });

        if ($to === OrderStatus::Delivered) {
            OrderDelivered::dispatch($order);
        }

        return $order;
    }
}
