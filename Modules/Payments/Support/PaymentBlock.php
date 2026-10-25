<?php

namespace Modules\Payments\Support;

use Modules\Payments\Enums\PaymentStatus;
use Modules\Payments\Models\Payment;
use Modules\Payments\Models\PaymentMethod;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Models\Order;

/**
 * What a Customer needs to pay an Order, as plain values for the Storefront:
 * where to transfer, how much, the state of their Receipts and whether they can send one now.
 */
class PaymentBlock
{
    /**
     * @return array{
     *     state: string,
     *     amount: string,
     *     reference: string,
     *     account: array{name: string, number: string, instructions: string|null}|null,
     *     expiresAt: string|null,
     *     attemptsUsed: int,
     *     attemptsLeft: int,
     *     maxAttempts: int,
     *     canSubmit: bool,
     *     rejectionReason: string|null,
     *     receiptUrl: string|null,
     *     maxKilobytes: int,
     *     acceptedTypes: list<string>
     * }
     */
    public function for(Order $order, string $submitUrl): array
    {
        $payments = Payment::query()->where('order_id', $order->id)->orderBy('attempt')->get();
        $latest = $payments->last();
        $max = (int) config('payments.max_attempts');
        $left = max(0, $max - $payments->count());
        $open = $order->status === OrderStatus::PendingPayment;
        $expired = $order->expires_at !== null && $order->expires_at->lte(now());
        $canSubmit = $open && ! $expired && $left > 0;

        return [
            'state' => $this->state($order, $latest, $left),
            'amount' => $order->total()->format(),
            'reference' => $order->number,
            'account' => $latest?->method_snapshot ?? PaymentMethod::activeFor($order->payment_method)?->details(),
            'expiresAt' => $open ? $order->expires_at?->toIso8601String() : null,
            'attemptsUsed' => $payments->count(),
            'attemptsLeft' => $left,
            'maxAttempts' => $max,
            'canSubmit' => $canSubmit,
            'rejectionReason' => $latest?->status === PaymentStatus::Rejected && $open ? $latest->rejection_reason : null,
            'receiptUrl' => $latest === null ? null : PaymentLinks::receipt($latest),
            'submitUrl' => $submitUrl,
            'maxKilobytes' => (int) config('payments.max_receipt_kilobytes'),
            'acceptedTypes' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
        ];
    }

    private function state(Order $order, ?Payment $latest, int $attemptsLeft): string
    {
        return match (true) {
            $order->status === OrderStatus::PaymentReview => 'under_review',
            $order->status === OrderStatus::PendingPayment && $latest === null => 'awaiting_receipt',
            $order->status === OrderStatus::PendingPayment => $attemptsLeft > 0 ? 'rejected' : 'exhausted',
            in_array($order->status, [OrderStatus::Cancelled], true) => 'closed',
            default => 'paid',
        };
    }
}
