<?php

namespace Modules\Storefront\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Modules\Auth\Models\Customer;
use Modules\Payments\Actions\StartOnlinePayment;
use Modules\Payments\Contracts\PaymentGateway;
use Modules\Payments\Exceptions\OnlinePaymentFailed;
use Modules\Payments\Exceptions\ReceiptCannotBeSubmitted;
use Modules\Sales\Models\Order;
use Modules\Storefront\Support\ThemeRenderer;
use Modules\Tenancy\Models\Store;
use Symfony\Component\HttpFoundation\Response;

/**
 * A Customer sending proof of transfer for their own Order.
 */
class PaymentController
{
    public function __construct(private readonly ThemeRenderer $renderer) {}

    public function store(Request $request, string $store, string $number, PaymentGateway $gateway): Response|RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $order = Order::query()->where('customer_id', $customer->id)->where('number', $number)->first();

        if ($order === null) {
            return $this->renderer->notFound();
        }

        $data = $request->validate([
            'receipt' => ['required', 'file'],
            'transfer_reference' => ['nullable', 'string', 'max:100'],
            'sender_phone' => ['nullable', 'string', 'max:30'],
        ]);

        try {
            $gateway->submit($order, $data['receipt'], $data['transfer_reference'] ?? null, $data['sender_phone'] ?? null);
        } catch (ReceiptCannotBeSubmitted $exception) {
            throw ValidationException::withMessages([$exception->field => $exception->getMessage()]);
        }

        return redirect('/'.Store::current()->slug."/orders/{$order->number}")->with('status', __('payments::receipt.submitted'));
    }

    /**
     * Sends the Customer to Fawaterk's hosted checkout for their own unpaid Order.
     */
    public function online(string $store, string $number, StartOnlinePayment $start): Response|RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $order = Order::query()->where('customer_id', $customer->id)->where('number', $number)->first();

        if ($order === null) {
            return $this->renderer->notFound();
        }

        try {
            $url = $start($order);
        } catch (OnlinePaymentFailed $exception) {
            return redirect('/'.Store::current()->slug."/orders/{$order->number}")->withErrors(['online' => $exception->getMessage()]);
        }

        return Inertia::location($url);
    }
}
