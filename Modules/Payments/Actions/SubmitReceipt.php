<?php

namespace Modules\Payments\Actions;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Auth\Enums\UserRole;
use Modules\Auth\Support\PhoneNumber;
use Modules\Core\Support\StoreContext;
use Modules\Core\Support\StoreStorage;
use Modules\Payments\Enums\PaymentStatus;
use Modules\Payments\Exceptions\ReceiptCannotBeSubmitted;
use Modules\Payments\Models\Payment;
use Modules\Payments\Models\PaymentMethod;
use Modules\Payments\Models\ReferenceFingerprint;
use Modules\Payments\Notifications\ReceiptAwaitingReview;
use Modules\Payments\Notifications\ReceiptReceived;
use Modules\Payments\Support\PaymentLinks;
use Modules\Payments\Support\ReceiptInspector;
use Modules\Payments\Support\ReferenceFingerprinter;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Exceptions\InvalidOrderTransition;
use Modules\Sales\Models\Order;
use Modules\Sales\Services\OrderStateMachine;

/**
 * Records a Customer's Receipt for an Order awaiting payment and sends the
 * Order to Payment Review, where its expiry no longer applies.
 */
class SubmitReceipt
{
    public function __construct(
        private readonly ReceiptInspector $inspector,
        private readonly OrderStateMachine $machine,
        private readonly StoreContext $context,
    ) {}

    /**
     * @throws ReceiptCannotBeSubmitted
     */
    public function __invoke(Order $order, UploadedFile $receipt, ?string $transferReference = null, ?string $senderPhone = null): Payment
    {
        $file = $this->inspector->inspect($receipt);
        $senderPhone = $this->normalizePhone($senderPhone);
        $transferReference = $transferReference === null || trim($transferReference) === '' ? null : trim($transferReference);

        $payment = $this->context->runAs($order->store_id, function () use ($order, $file, $transferReference, $senderPhone): Payment {
            $path = StoreStorage::path("receipts/{$order->id}/".Str::random(40).'.'.$file->extension);
            $disk = Storage::disk(config('payments.disk'));
            $stored = false;

            try {
                $payment = DB::transaction(function () use ($order, $file, $transferReference, $senderPhone, $path, $disk, &$stored): Payment {
                    $locked = Order::query()->lockForUpdate()->findOrFail($order->getKey());

                    $this->assertCanSubmit($locked);

                    $attempt = Payment::query()->where('order_id', $locked->id)->count() + 1;
                    $disk->put($path, $file->contents, 'private');
                    $stored = true;

                    $payment = Payment::query()->create([
                        'order_id' => $locked->id,
                        'method' => $locked->payment_method,
                        'amount' => $locked->total,
                        'status' => PaymentStatus::PendingReview,
                        'attempt' => $attempt,
                        'receipt_path' => $path,
                        'receipt_mime' => $file->mime,
                        'transfer_reference' => $transferReference,
                        'sender_phone' => $senderPhone,
                        'method_snapshot' => $this->firstSnapshot($locked) ?? PaymentMethod::activeFor($locked->payment_method)?->details(),
                    ]);

                    $this->flagDuplicate($payment);

                    try {
                        $this->machine->transition($locked, OrderStatus::PaymentReview, 'customer', $locked->customer_id, null, [OrderStatus::PendingPayment]);
                    } catch (InvalidOrderTransition) {
                        throw new ReceiptCannotBeSubmitted('order', __('payments::receipt.errors.not_payable'));
                    }

                    return $payment;
                });
            } catch (\Throwable $exception) {
                if ($stored) {
                    $disk->delete($path);
                }

                throw $exception;
            }

            $order->refresh()->load('customer');

            return $payment;
        });

        $order->customer?->notify(new ReceiptReceived($order->number, PaymentLinks::customerOrder($order)));
        Notification::send(User::query()->where('role', UserRole::Admin->value)->get(), new ReceiptAwaitingReview($order->number, PaymentLinks::adminQueue()));

        return $payment;
    }

    private function assertCanSubmit(Order $order): void
    {
        if ($order->status !== OrderStatus::PendingPayment) {
            throw new ReceiptCannotBeSubmitted('order', $order->status === OrderStatus::PaymentReview
                ? __('payments::receipt.errors.under_review')
                : __('payments::receipt.errors.not_payable'));
        }

        if ($order->expires_at !== null && $order->expires_at->lte(now())) {
            throw new ReceiptCannotBeSubmitted('order', __('payments::receipt.errors.expired'));
        }

        $payments = Payment::query()->where('order_id', $order->id)->get(['status']);

        if ($payments->contains(fn (Payment $payment): bool => $payment->status === PaymentStatus::PendingReview)) {
            throw new ReceiptCannotBeSubmitted('order', __('payments::receipt.errors.under_review'));
        }

        if ($payments->count() >= config('payments.max_attempts')) {
            throw new ReceiptCannotBeSubmitted('order', __('payments::receipt.errors.no_attempts_left'));
        }
    }

    /**
     * The account the Order was first paid against, so later edits to the Platform's account do not change it.
     *
     * @return array{name: string, number: string, instructions: string|null}|null
     */
    private function firstSnapshot(Order $order): ?array
    {
        return Payment::query()->where('order_id', $order->id)->whereNotNull('method_snapshot')->orderBy('attempt')->first()?->method_snapshot;
    }

    private function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        return PhoneNumber::normalize($phone)
            ?? throw new ReceiptCannotBeSubmitted('sender_phone', __('payments::receipt.errors.sender_phone'));
    }

    /**
     * A reference seen before is flagged, never refused.
     */
    private function flagDuplicate(Payment $payment): void
    {
        $fingerprint = $payment->transfer_reference === null ? null : ReferenceFingerprinter::fingerprint($payment->transfer_reference);

        if ($fingerprint === null) {
            return;
        }

        DB::table('payment_reference_fingerprints')->insertOrIgnore([
            'fingerprint' => $fingerprint,
            'first_payment_id' => $payment->id,
            'created_at' => now(),
        ]);

        $first = ReferenceFingerprint::query()->where('fingerprint', $fingerprint)->value('first_payment_id');

        if ($first !== null && (int) $first !== $payment->id) {
            $payment->forceFill(['duplicate_of_id' => $first])->save();
        }
    }
}
