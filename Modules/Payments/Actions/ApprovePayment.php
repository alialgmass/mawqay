<?php

namespace Modules\Payments\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Services\StockService;
use Modules\Core\Support\Audit;
use Modules\Core\Support\StoreContext;
use Modules\Payments\Enums\PaymentStatus;
use Modules\Payments\Events\PaymentApproved;
use Modules\Payments\Exceptions\PaymentCannotBeReviewed;
use Modules\Payments\Models\Payment;
use Modules\Payments\Notifications\PaymentWasApproved;
use Modules\Payments\Support\PaymentLinks;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Exceptions\InvalidOrderTransition;
use Modules\Sales\Models\Order;
use Modules\Sales\Services\OrderStateMachine;

/**
 * Accepts a Receipt: the Order becomes paid and its held Stock becomes a sale.
 * Approving the same Payment again changes and announces nothing.
 */
class ApprovePayment
{
    public function __construct(
        private readonly OrderStateMachine $machine,
        private readonly StockService $stock,
        private readonly StoreContext $context,
    ) {}

    /**
     * @throws PaymentCannotBeReviewed
     */
    public function __invoke(Payment $payment, int $reviewerId): Payment
    {
        $approvedNow = false;

        $order = $this->context->runAs($payment->store_id, function () use ($payment, $reviewerId, &$approvedNow): Order {
            $order = DB::transaction(function () use ($payment, $reviewerId, &$approvedNow): Order {
                $locked = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
                $order = Order::query()->findOrFail($locked->order_id);

                if ($locked->status === PaymentStatus::Approved) {
                    $payment->setRawAttributes($locked->getAttributes(), true);

                    return $order;
                }

                if ($locked->status !== PaymentStatus::PendingReview) {
                    throw new PaymentCannotBeReviewed(__('payments::review.errors.not_pending'));
                }

                try {
                    $this->machine->transition($order, OrderStatus::Paid, 'admin', $reviewerId, null, [OrderStatus::PaymentReview]);
                } catch (InvalidOrderTransition) {
                    throw new PaymentCannotBeReviewed(__('payments::review.errors.order_not_in_review'));
                }

                foreach ($order->items()->with('variant')->orderBy('variant_id')->get() as $item) {
                    $this->stock->commit($item->variant, $item->quantity);
                }

                $locked->forceFill(['status' => PaymentStatus::Approved, 'reviewed_by' => $reviewerId, 'reviewed_at' => now(), 'rejection_reason' => null])->save();
                Audit::record('payment_approved', $locked, ['status' => PaymentStatus::PendingReview->value], ['status' => PaymentStatus::Approved->value, 'amount' => $locked->amount], null, $locked->store_id, $reviewerId);
                $payment->setRawAttributes($locked->getAttributes(), true);
                $approvedNow = true;

                return $order;
            });

            return $order->load('customer');
        });

        if ($approvedNow) {
            PaymentApproved::dispatch($payment, $order);
            $order->customer?->notify(new PaymentWasApproved($order->number, PaymentLinks::customerOrder($order)));
        }

        return $payment;
    }
}
