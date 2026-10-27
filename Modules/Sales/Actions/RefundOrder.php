<?php

namespace Modules\Sales\Actions;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Variant;
use Modules\Catalog\Services\StockService;
use Modules\Core\Money\Money;
use Modules\Core\Support\Audit;
use Modules\Sales\Contracts\ReversesOrderMoney;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Enums\RefundReason;
use Modules\Sales\Events\OrderRefunded;
use Modules\Sales\Exceptions\RefundNotAllowed;
use Modules\Sales\Models\Order;
use Modules\Sales\Models\OrderItem;
use Modules\Sales\Models\Refund;
use Modules\Sales\Services\OrderStateMachine;
use Modules\Sales\Support\OrderNotifier;

/**
 * A Platform Admin hands money back for an Order, in full or in part. The books are reversed,
 * an Order with nothing left to refund becomes Refunded, and the goods may go back on the shelf.
 */
class RefundOrder
{
    public function __construct(
        private readonly ReversesOrderMoney $books,
        private readonly OrderStateMachine $machine,
        private readonly StockService $stock,
        private readonly OrderNotifier $notifier,
    ) {}

    /**
     * @param  int|null  $amount  piasters; null refunds everything still refundable
     * @param  string  $reference  names this submission so sending it twice refunds once
     * @param  bool  $restock  put the goods back in stock
     * @param  array<int, int>  $restockQuantities  order item id => quantity; empty with $restock means everything not yet restocked
     * @param  string|null  $overrideReason  required to refund a Completed Order
     *
     * @throws RefundNotAllowed
     */
    public function __invoke(
        Order $order,
        ?int $amount,
        RefundReason $reason,
        ?string $note,
        ?int $actorId,
        string $reference,
        bool $restock = false,
        array $restockQuantities = [],
        ?string $overrideReason = null,
    ): Refund {
        $existing = Refund::query()->where('reference', $reference)->where('order_id', $order->getKey())->first();

        if ($existing !== null) {
            return $existing;
        }

        try {
            $refund = $this->record($order, $amount, $reason, $note, $actorId, $reference, $restock, $restockQuantities, $overrideReason);
        } catch (UniqueConstraintViolationException) {
            // A second submission with the same reference won the race: that refund is the answer.
            return Refund::query()->where('reference', $reference)->where('order_id', $order->getKey())->firstOrFail();
        }

        $order->refresh();
        OrderRefunded::dispatch($order, $refund);

        $replace = ['amount' => Money::fromPiasters($refund->amount)->format()];
        $this->notifier->customer($order, 'refunded', $replace);
        $this->notifier->vendor($order, 'vendor_refunded', $replace);

        return $refund;
    }

    /**
     * @param  array<int, int>  $restockQuantities
     */
    private function record(Order $order, ?int $amount, RefundReason $reason, ?string $note, ?int $actorId, string $reference, bool $restock, array $restockQuantities, ?string $overrideReason): Refund
    {
        return DB::transaction(function () use ($order, $amount, $reason, $note, $actorId, $reference, $restock, $restockQuantities, $overrideReason): Refund {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            $this->assertRefundable($locked, $overrideReason);

            $earlier = Refund::query()->where('order_id', $locked->id)->get();
            $refundable = $locked->total - (int) $earlier->sum('amount');
            $amount ??= $refundable;

            if ($amount < 1 || $amount > $refundable) {
                throw new RefundNotAllowed("Cannot refund {$amount}; {$refundable} is left to refund.");
            }

            $restocked = $restock ? $this->restockPlan($locked, $earlier, $restockQuantities, $amount === $refundable) : [];

            $refund = Refund::query()->create([
                'store_id' => $locked->store_id,
                'order_id' => $locked->id,
                'amount' => $amount,
                'reason' => $reason,
                'note' => $note,
                'restocked' => $restock,
                'restock_items' => $restocked === [] ? null : $restocked,
                'actor_id' => $actorId,
                'reference' => $reference,
            ]);

            $this->books->reverse($locked, $amount, "refund:{$refund->id}");

            Audit::record('order_refunded', $locked, ['refunded_total' => (int) $earlier->sum('amount')], ['refunded_total' => (int) $earlier->sum('amount') + $amount, 'refund_id' => $refund->id, 'amount' => $amount], $reason->value.($note === null ? '' : ": {$note}"), $locked->store_id, $actorId);

            foreach ($restocked as $itemId => $quantity) {
                $variant = Variant::query()->find(OrderItem::query()->whereKey($itemId)->value('variant_id'));

                if ($variant !== null) {
                    $this->stock->restock($variant, $quantity);
                }
            }

            if ($locked->status === OrderStatus::Completed) {
                Audit::record('refund_of_completed_order', $locked, null, ['refund_id' => $refund->id, 'amount' => $amount], (string) $overrideReason, $locked->store_id, $actorId);
            }

            if ($amount === $refundable) {
                $this->machine->transition($locked, OrderStatus::Refunded, 'admin', $actorId, 'Refund: '.$reason->value, [$locked->status]);
            }

            return $refund;
        });
    }

    /**
     * @throws RefundNotAllowed
     */
    private function assertRefundable(Order $order, ?string $overrideReason): void
    {
        if ($order->status === OrderStatus::Completed) {
            if (blank($overrideReason)) {
                throw new RefundNotAllowed('A completed Order needs an override reason to be refunded.');
            }

            return;
        }

        $allowed = [OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered];

        if (! in_array($order->status, $allowed, true)) {
            throw new RefundNotAllowed("An Order in status {$order->status->value} cannot be refunded.");
        }
    }

    /**
     * @param  iterable<int, Refund>  $earlier
     * @param  array<int, int>  $requested
     * @return array<int, int> order item id => quantity to put back
     *
     * @throws RefundNotAllowed
     */
    private function restockPlan(Order $order, iterable $earlier, array $requested, bool $isFinal): array
    {
        $already = [];

        foreach ($earlier as $refund) {
            foreach ($refund->restock_items ?? [] as $itemId => $quantity) {
                $already[$itemId] = ($already[$itemId] ?? 0) + $quantity;
            }
        }

        $remaining = $order->items()->get()->mapWithKeys(fn (OrderItem $item): array => [$item->id => $item->quantity - ($already[$item->id] ?? 0)])->all();

        if ($requested === []) {
            if (! $isFinal) {
                throw new RefundNotAllowed('Choose the quantities to restock for a partial refund.');
            }

            return array_filter($remaining, fn (int $quantity): bool => $quantity > 0);
        }

        $plan = [];

        foreach ($requested as $itemId => $quantity) {
            if ($quantity < 1) {
                continue;
            }

            if (! isset($remaining[$itemId]) || $quantity > $remaining[$itemId]) {
                throw new RefundNotAllowed("Cannot restock {$quantity} of item {$itemId}.");
            }

            $plan[(int) $itemId] = (int) $quantity;
        }

        return $plan;
    }
}
