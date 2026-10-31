<?php

namespace Modules\Payments\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Support\StoreContext;
use Modules\Payments\Enums\PaymentStatus;
use Modules\Payments\Exceptions\OnlinePaymentFailed;
use Modules\Payments\Models\Payment;
use Modules\Payments\Support\FawaterkClient;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Models\Order;
use Modules\Tenancy\Models\Store;

/**
 * Opens a Fawaterk hosted checkout for an unpaid Order and returns where to send the Customer.
 * An earlier unfinished attempt is superseded, so only the newest link can settle the Order.
 */
class StartOnlinePayment
{
    public const METHOD = 'fawaterk';

    public function __construct(
        private readonly FawaterkClient $client,
        private readonly StoreContext $context,
    ) {}

    /**
     * @throws OnlinePaymentFailed
     */
    public function __invoke(Order $order): string
    {
        $this->assertPayable($order);

        return $this->context->runAs($order->store_id, function () use ($order): string {
            $order->loadMissing('customer');
            $slug = Store::query()->whereKey($order->store_id)->value('slug');
            $orderUrl = url("/{$slug}/orders/{$order->number}");

            $transaction = $this->client->createTransaction([
                'cartTotal' => $order->total / 100,
                'currency' => config('sales.currency', 'EGP'),
                'customer' => array_filter([
                    'first_name' => Str::before((string) $order->customer?->name, ' ') ?: 'Customer',
                    'last_name' => trim(Str::after((string) $order->customer?->name, ' ')) ?: '-',
                    'email' => $order->customer?->email,
                    'phone' => $order->customer?->phone,
                    'customer_unique_id' => 'customer-'.$order->customer_id,
                ]),
                'cartItems' => [['name' => "Order {$order->number}", 'price' => $order->total / 100, 'quantity' => 1]],
                'pay_load' => ['order_number' => $order->number, 'store_id' => $order->store_id],
                'redirectionUrls' => [
                    'successUrl' => $orderUrl,
                    'failUrl' => $orderUrl,
                    'pendingUrl' => $orderUrl,
                    'backUrl' => $orderUrl,
                    'webhookUrl' => url('/webhooks/fawaterk_json'),
                ],
                'lang' => app()->getLocale() === 'ar' ? 'ar' : 'en',
            ]);

            DB::transaction(function () use ($order, $transaction): void {
                $locked = Order::query()->lockForUpdate()->findOrFail($order->getKey());
                $this->assertPayable($locked);

                Payment::query()->where('order_id', $locked->id)->where('status', PaymentStatus::AwaitingGateway->value)
                    ->update(['status' => PaymentStatus::Rejected->value, 'rejection_reason' => 'superseded']);

                Payment::query()->create([
                    'order_id' => $locked->id,
                    'method' => $locked->payment_method,
                    'gateway' => self::METHOD,
                    'gateway_reference' => $transaction['intent_key'],
                    'amount' => $locked->total,
                    'status' => PaymentStatus::AwaitingGateway,
                    'attempt' => Payment::query()->where('order_id', $locked->id)->count() + 1,
                ]);
            });

            return $transaction['url'];
        });
    }

    private function assertPayable(Order $order): void
    {
        if ($order->payment_method !== self::METHOD || $order->status !== OrderStatus::PendingPayment || ! $this->client->isConfigured()) {
            throw new OnlinePaymentFailed(__('payments::online.errors.not_payable'));
        }

        if ($order->expires_at !== null && $order->expires_at->lte(now())) {
            throw new OnlinePaymentFailed(__('payments::online.errors.expired'));
        }
    }
}
