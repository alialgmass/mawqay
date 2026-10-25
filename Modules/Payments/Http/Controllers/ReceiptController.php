<?php

namespace Modules\Payments\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\Customer;
use Modules\Core\Support\StoreContext;
use Modules\Payments\Models\Payment;
use Symfony\Component\HttpFoundation\Response;

/**
 * Streams a Receipt from private storage. The signed link is not enough on its
 * own: the visitor must also be a Platform Admin or the Order's own Customer.
 */
class ReceiptController
{
    public function __construct(private readonly StoreContext $context) {}

    public function __invoke(int $store, int $payment): Response
    {
        $record = $this->context->runAs($store, fn (): ?Payment => Payment::query()->with('order')->find($payment));

        abort_if($record === null || ! $this->mayView($record), 403);

        $disk = Storage::disk(config('payments.disk'));

        abort_unless($disk->exists($record->receipt_path), 404);

        return $disk->response($record->receipt_path, null, [
            'Content-Type' => $record->receipt_mime,
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function mayView(Payment $payment): bool
    {
        $user = Auth::guard('web')->user();

        if ($user instanceof User && $user->isPlatformAdmin()) {
            return true;
        }

        $customer = Auth::guard('customer')->user();

        return $customer instanceof Customer
            && $customer->store_id === $payment->store_id
            && $customer->id === $payment->order?->customer_id;
    }
}
