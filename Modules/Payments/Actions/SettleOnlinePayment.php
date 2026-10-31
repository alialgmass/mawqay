<?php

namespace Modules\Payments\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Catalog\Services\StockService;
use Modules\Core\Scopes\StoreScope;
use Modules\Core\Support\Audit;
use Modules\Core\Support\StoreContext;
use Modules\Payments\Enums\PaymentStatus;
use Modules\Payments\Events\PaymentApproved;
use Modules\Payments\Models\Payment;
use Modules\Payments\Notifications\PaymentWasApproved;
use Modules\Payments\Support\PaymentLinks;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Exceptions\InvalidOrderTransition;
use Modules\Sales\Models\Order;
use Modules\Sales\Services\OrderStateMachine;

/**
 * Applies a verified Fawaterk webhook to the Payment it belongs to: paid settles the Order,
 * failed reopens it for another try. Safe to receive twice.
 */
class SettleOnlinePayment
{
    public function __construct(
        private readonly OrderStateMachine $machine,
        private readonly StockService $stock,
        private readonly StoreContext $context,
    ) {}

    /**
     * @param  array<string, mixed>  $payload  the webhook body, kept on the Payment for the record
     */
    public function paid(string $reference, ?string $transactionId, ?string $paidAmount, array $payload): void
    {
        $payment = $this->find($reference);

        if ($payment === null) {
            Log::warning('Fawaterk paid webhook for an unknown reference.', ['reference' => $reference]);

            return;
        }

        $approvedNow = false;
        $order = null;

        $this->context->runAs($payment->store_id, function () use ($payment, $transactionId, $paidAmount, $payload, &$approvedNow, &$order): void {
            DB::transaction(function () use ($payment, $transactionId, $paidAmount, $payload, &$approvedNow, &$order): void {
                $locked = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
                $order = Order::query()->lockForUpdate()->findOrFail($locked->order_id);

                if ($locked->status === PaymentStatus::Approved) {
                    return;
                }

                $locked->forceFill(['gateway_transaction_id' => $transactionId, 'gateway_payload' => $payload])->save();

                if ($paidAmount !== null && (int) round(((float) $paidAmount) * 100) !== (int) $locked->amount) {
                    Audit::record('online_payment_amount_mismatch', $locked, null, ['paid' => $paidAmount, 'expected' => $locked->amount], null, $locked->store_id);

                    return;
                }

                try {
                    $this->machine->transition($order, OrderStatus::PaymentReview, 'system', null, null, [OrderStatus::PendingPayment]);
                    $this->machine->transition($order, OrderStatus::Paid, 'system', null, null, [OrderStatus::PaymentReview]);
                } catch (InvalidOrderTransition) {
                    Audit::record('online_payment_orphaned', $locked, null, ['order_status' => $order->status->value], null, $locked->store_id);
                    Log::warning('Fawaterk payment arrived for an order that is no longer payable.', ['order' => $order->number]);

                    return;
                }

                foreach ($order->items()->with('variant')->orderBy('variant_id')->get() as $item) {
                    $this->stock->commit($item->variant, $item->quantity);
                }

                $locked->forceFill(['status' => PaymentStatus::Approved, 'reviewed_at' => now(), 'rejection_reason' => null])->save();
                Audit::record('payment_approved', $locked, ['status' => PaymentStatus::AwaitingGateway->value], ['status' => PaymentStatus::Approved->value, 'amount' => $locked->amount, 'gateway' => 'fawaterk'], null, $locked->store_id);
                $payment->setRawAttributes($locked->getAttributes(), true);
                $approvedNow = true;
            });
        });

        if ($approvedNow && $order !== null) {
            $order->refresh()->load('customer');
            PaymentApproved::dispatch($payment, $order);
            $order->customer?->notify(new PaymentWasApproved($order->number, PaymentLinks::customerOrder($order)));
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function failed(string $reference, ?string $reason, array $payload): void
    {
        $payment = $this->find($reference);

        if ($payment === null || $payment->status !== PaymentStatus::AwaitingGateway) {
            return;
        }

        $this->context->runAs($payment->store_id, function () use ($payment, $reason, $payload): void {
            Payment::query()->whereKey($payment->getKey())->where('status', PaymentStatus::AwaitingGateway->value)->update([
                'status' => PaymentStatus::Rejected->value,
                'rejection_reason' => $reason ?: __('payments::online.errors.failed'),
                'gateway_payload' => json_encode($payload),
                'updated_at' => now(),
            ]);
        });
    }

    private function find(string $reference): ?Payment
    {
        return Payment::query()->withoutGlobalScope(StoreScope::class)->where('gateway', StartOnlinePayment::METHOD)->where('gateway_reference', $reference)->first();
    }
}
