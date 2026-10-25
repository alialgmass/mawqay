<?php

namespace Modules\Payments\Filament\Admin\Support;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Modules\Core\Money\Money;
use Modules\Core\Support\AdminScope;
use Modules\Payments\Models\Payment;
use Modules\Payments\Support\PaymentLinks;
use Modules\Sales\Filament\Admin\Resources\Orders\Pages\ViewOrder;

/**
 * The payment history shown under an Order in the Admin Panel. Sales does not
 * know about Payments, so the Admin Order page is extended from this side.
 */
class OrderPaymentHistory
{
    public function render(): ?View
    {
        $page = Livewire::current();

        if (! $page instanceof ViewOrder) {
            return null;
        }

        /** @var Collection<int, Payment> $payments */
        $payments = AdminScope::on(Payment::class)->where('order_id', $page->getRecord()->getKey())->orderBy('attempt')->get();
        $reviewers = User::query()->whereIn('id', $payments->pluck('reviewed_by')->filter())->pluck('name', 'id');

        return view('payments::order-payment-history', [
            'payments' => $payments->map(fn (Payment $payment): array => [
                'attempt' => $payment->attempt,
                'status' => __('payments::review.payment.statuses.'.$payment->status->value),
                'amount' => Money::fromPiasters($payment->amount)->format(),
                'reference' => $payment->transfer_reference,
                'duplicate' => $payment->isDuplicate(),
                'submittedAt' => $payment->created_at->toDateTimeString(),
                'reviewer' => $reviewers[$payment->reviewed_by] ?? null,
                'reviewedAt' => $payment->reviewed_at?->toDateTimeString(),
                'reason' => $payment->rejection_reason,
                'receiptUrl' => PaymentLinks::receipt($payment),
            ])->all(),
        ]);
    }
}
