<?php

namespace Modules\Payments\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Core\Support\PlatformSettings;
use Modules\Core\Support\StoreContext;
use Modules\Payments\Enums\PaymentStatus;
use Modules\Payments\Exceptions\PaymentCannotBeReviewed;
use Modules\Payments\Models\Payment;
use Modules\Payments\Notifications\PaymentWasRejected;
use Modules\Payments\Support\PaymentLinks;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Exceptions\InvalidOrderTransition;
use Modules\Sales\Models\Order;
use Modules\Sales\Services\OrderStateMachine;

/**
 * Turns a Receipt down with a reason. The Order goes back to awaiting payment
 * on the shorter retry window, not a fresh Reservation Window.
 */
class RejectPayment
{
    public function __construct(
        private readonly OrderStateMachine $machine,
        private readonly PlatformSettings $settings,
        private readonly StoreContext $context,
    ) {}

    /**
     * @throws PaymentCannotBeReviewed
     */
    public function __invoke(Payment $payment, int $reviewerId, string $reason): Payment
    {
        $rejectedNow = false;

        $order = $this->context->runAs($payment->store_id, function () use ($payment, $reviewerId, $reason, &$rejectedNow): Order {
            $order = DB::transaction(function () use ($payment, $reviewerId, $reason, &$rejectedNow): Order {
                $locked = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
                $order = Order::query()->findOrFail($locked->order_id);

                if ($locked->status === PaymentStatus::Rejected) {
                    $payment->setRawAttributes($locked->getAttributes(), true);

                    return $order;
                }

                if ($locked->status !== PaymentStatus::PendingReview) {
                    throw new PaymentCannotBeReviewed(__('payments::review.errors.not_pending'));
                }

                try {
                    $this->machine->transition($order, OrderStatus::PendingPayment, 'admin', $reviewerId, $reason, [OrderStatus::PaymentReview]);
                } catch (InvalidOrderTransition) {
                    throw new PaymentCannotBeReviewed(__('payments::review.errors.order_not_in_review'));
                }

                $order->forceFill(['expires_at' => now()->addHours($this->settings->paymentRetryWindowHours())])->save();

                $locked->forceFill(['status' => PaymentStatus::Rejected, 'reviewed_by' => $reviewerId, 'reviewed_at' => now(), 'rejection_reason' => $reason])->save();
                $payment->setRawAttributes($locked->getAttributes(), true);
                $rejectedNow = true;

                return $order;
            });

            return $order->load('customer');
        });

        if ($rejectedNow) {
            $order->customer?->notify(new PaymentWasRejected($order->number, $reason, PaymentLinks::customerOrder($order)));
        }

        return $payment;
    }
}
