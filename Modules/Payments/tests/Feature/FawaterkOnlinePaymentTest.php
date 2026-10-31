<?php

namespace Modules\Payments\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Auth\Models\Customer;
use Modules\Catalog\Models\Variant;
use Modules\Core\Support\StoreContext;
use Modules\Payments\Actions\StartOnlinePayment;
use Modules\Payments\Enums\PaymentStatus;
use Modules\Payments\Exceptions\OnlinePaymentFailed;
use Modules\Payments\Models\Payment;
use Modules\Payments\Testing\BuildsPayments;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Models\Order;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class FawaterkOnlinePaymentTest extends TestCase
{
    use BuildsPayments;
    use RefreshDatabase;

    private const KEY = 'test-hash-key';

    private const INTENT = '550e8400-e29b-41d4-a716-446655440000';

    private Store $store;

    private Customer $customer;

    private Variant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payments.fawaterk.client_id' => 'client',
            'payments.fawaterk.client_secret' => 'secret',
            'payments.fawaterk.hash_key' => self::KEY,
            'payments.fawaterk.base_url' => 'https://fawaterk.test',
        ]);
        Cache::flush();

        $this->store = $this->shop('store-a', ['name' => 'Alpha']);
        [$this->customer] = $this->customerWithAddress($this->store);
        $this->variant = $this->variant($this->store, stock: 10);
    }

    private function onlineOrder(array $attributes = []): Order
    {
        return $this->orderFor($this->store, $this->customer, OrderStatus::PendingPayment, $this->variant, 2, ['payment_method' => 'fawaterk', ...$attributes]);
    }

    private function fakeFawaterk(): void
    {
        Http::fake([
            'fawaterk.test/oauth/token' => Http::response(['token_type' => 'Bearer', 'access_token' => 'tok', 'expires_in' => 31536000]),
            'fawaterk.test/api/v3/createTransaction' => Http::response([
                'status' => 'success',
                'message' => 'Transaction link created',
                'data' => ['intent_key' => self::INTENT, 'url' => 'https://fawaterk.test/transactions/'.self::INTENT, 'expires_in' => 2592000],
            ]),
        ]);
    }

    private function awaitingPayment(Order $order, string $reference = self::INTENT): Payment
    {
        return app(StoreContext::class)->runAs($order->store_id, fn (): Payment => Payment::query()->create([
            'order_id' => $order->id,
            'method' => 'fawaterk',
            'gateway' => 'fawaterk',
            'gateway_reference' => $reference,
            'amount' => $order->total,
            'status' => PaymentStatus::AwaitingGateway,
            'attempt' => 1,
        ]));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function paidPayload(Order $order, array $overrides = []): array
    {
        $payload = [
            'transaction_key' => self::INTENT,
            'transaction_id' => 12345,
            'payment_method' => 'Visa-Mastercard',
            'status' => 'paid',
            'paidAmount' => number_format($order->total / 100, 2, '.', ''),
            'paidCurrency' => 'EGP',
            ...$overrides,
        ];
        $payload['transactionHashKey'] = $overrides['transactionHashKey'] ?? $this->sign($payload);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sign(array $payload): string
    {
        return hash_hmac('sha256', 'TransactionId='.$payload['transaction_id'].'&TransactionKey='.$payload['transaction_key'].'&PaymentMethod='.$payload['payment_method'], self::KEY);
    }

    private function freshOrder(Order $order): Order
    {
        return app(StoreContext::class)->runAs($order->store_id, fn (): Order => Order::query()->findOrFail($order->id));
    }

    public function test_starting_an_online_payment_returns_the_hosted_checkout_url_and_records_the_attempt(): void
    {
        $this->fakeFawaterk();
        $order = $this->onlineOrder();

        $url = app(StartOnlinePayment::class)($order);

        $this->assertSame('https://fawaterk.test/transactions/'.self::INTENT, $url);

        $payment = app(StoreContext::class)->runAs($order->store_id, fn (): Payment => Payment::query()->where('order_id', $order->id)->firstOrFail());
        $this->assertSame(PaymentStatus::AwaitingGateway, $payment->status);
        $this->assertSame(self::INTENT, $payment->gateway_reference);
        $this->assertSame($order->total, $payment->amount);
        $this->assertSame(OrderStatus::PendingPayment, $this->freshOrder($order)->status);

        Http::assertSent(fn ($request) => $request->url() === 'https://fawaterk.test/api/v3/createTransaction'
            && $request->header('Authorization') === ['Bearer tok']
            && (float) $request['cartTotal'] === (float) ($order->total / 100)
            && $request['currency'] === 'EGP'
            && $request['redirectionUrls']['webhookUrl'] === url('/webhooks/fawaterk_json'));
    }

    public function test_a_new_attempt_supersedes_the_unfinished_one(): void
    {
        $this->fakeFawaterk();
        $order = $this->onlineOrder();
        $first = $this->awaitingPayment($order, 'older-intent');

        app(StartOnlinePayment::class)($order);

        $this->assertSame(PaymentStatus::Rejected, app(StoreContext::class)->runAs($order->store_id, fn () => $first->fresh()->status));
        $this->assertSame(2, app(StoreContext::class)->runAs($order->store_id, fn (): int => Payment::query()->where('order_id', $order->id)->count()));
    }

    public function test_only_pending_fawaterk_orders_can_start_online_payment(): void
    {
        $this->fakeFawaterk();

        foreach ([
            $this->orderFor($this->store, $this->customer, OrderStatus::PendingPayment, $this->variant, 1),
            $this->onlineOrder(['status' => OrderStatus::Paid]),
            $this->onlineOrder(['expires_at' => now()->subMinute()]),
        ] as $order) {
            try {
                app(StartOnlinePayment::class)($order);
                $this->fail('Expected the payment not to start.');
            } catch (OnlinePaymentFailed) {
                $this->assertTrue(true);
            }
        }

        Http::assertNothingSent();
    }

    public function test_a_gateway_outage_is_reported_without_leaving_a_payment_behind(): void
    {
        Http::fake([
            'fawaterk.test/oauth/token' => Http::response(['access_token' => 'tok']),
            'fawaterk.test/api/v3/createTransaction' => Http::response(['message' => 'boom'], 500),
        ]);
        $order = $this->onlineOrder();

        $this->expectException(OnlinePaymentFailed::class);

        try {
            app(StartOnlinePayment::class)($order);
        } finally {
            $this->assertSame(0, app(StoreContext::class)->runAs($order->store_id, fn (): int => Payment::query()->where('order_id', $order->id)->count()));
        }
    }

    public function test_a_signed_paid_webhook_settles_the_order_and_commits_stock(): void
    {
        $order = $this->onlineOrder();
        $payment = $this->awaitingPayment($order);

        $this->postJson('/webhooks/fawaterk_json', $this->paidPayload($order))->assertOk();

        $this->assertSame(OrderStatus::Paid, $this->freshOrder($order)->status);
        $payment = app(StoreContext::class)->runAs($order->store_id, fn () => $payment->fresh());
        $this->assertSame(PaymentStatus::Approved, $payment->status);
        $this->assertSame('12345', $payment->gateway_transaction_id);
        $this->assertSame(0, $this->variant->fresh()->reserved);
        $this->assertSame(8, $this->variant->fresh()->stock);
    }

    public function test_a_repeated_paid_webhook_changes_nothing(): void
    {
        $order = $this->onlineOrder();
        $this->awaitingPayment($order);
        $payload = $this->paidPayload($order);

        $this->postJson('/webhooks/fawaterk_json', $payload)->assertOk();
        $this->postJson('/webhooks/fawaterk_json', $payload)->assertOk();

        $this->assertSame(OrderStatus::Paid, $this->freshOrder($order)->status);
        $this->assertSame(8, $this->variant->fresh()->stock);
    }

    public function test_a_webhook_with_a_bad_signature_is_refused_and_changes_nothing(): void
    {
        $order = $this->onlineOrder();
        $this->awaitingPayment($order);

        $this->postJson('/webhooks/fawaterk_json', $this->paidPayload($order, ['transactionHashKey' => str_repeat('a', 64)]))->assertForbidden();
        $this->postJson('/webhooks/fawaterk_json', [...$this->paidPayload($order), 'payment_method' => 'Other'])->assertForbidden();
        $this->postJson('/webhooks/fawaterk_json', ['transaction_key' => self::INTENT])->assertForbidden();

        $this->assertSame(OrderStatus::PendingPayment, $this->freshOrder($order)->status);
    }

    public function test_a_pending_status_does_not_settle_the_order(): void
    {
        $order = $this->onlineOrder();
        $this->awaitingPayment($order);

        $this->postJson('/webhooks/fawaterk_json', $this->paidPayload($order, ['status' => 'pending']))->assertOk();

        $this->assertSame(OrderStatus::PendingPayment, $this->freshOrder($order)->status);
    }

    public function test_a_paid_amount_that_differs_from_the_order_total_is_not_accepted(): void
    {
        $order = $this->onlineOrder();
        $payment = $this->awaitingPayment($order);

        $this->postJson('/webhooks/fawaterk_json', $this->paidPayload($order, ['paidAmount' => '1.00']))->assertOk();

        $this->assertSame(OrderStatus::PendingPayment, $this->freshOrder($order)->status);
        $this->assertSame(PaymentStatus::AwaitingGateway, app(StoreContext::class)->runAs($order->store_id, fn () => $payment->fresh()->status));
    }

    public function test_a_payment_for_an_order_that_is_no_longer_payable_is_recorded_but_not_applied(): void
    {
        $order = $this->onlineOrder(['status' => OrderStatus::Cancelled]);
        $this->awaitingPayment($order);

        $this->postJson('/webhooks/fawaterk_json', $this->paidPayload($order))->assertOk();

        $this->assertSame(OrderStatus::Cancelled, $this->freshOrder($order)->status);
        $this->assertDatabaseHas('audit_entries', ['action' => 'online_payment_orphaned']);
    }

    public function test_an_unknown_reference_is_acknowledged_without_error(): void
    {
        $order = $this->onlineOrder();

        $this->postJson('/webhooks/fawaterk_json', $this->paidPayload($order))->assertOk();

        $this->assertSame(OrderStatus::PendingPayment, $this->freshOrder($order)->status);
    }

    public function test_a_signed_failed_webhook_rejects_the_attempt_and_keeps_the_order_open(): void
    {
        $order = $this->onlineOrder();
        $payment = $this->awaitingPayment($order);
        $payload = [
            'transaction_key' => self::INTENT,
            'transaction_id' => 12345,
            'payment_method' => 'Visa-Mastercard',
            'errorMessage' => 'Payment declined by issuer',
        ];
        $payload['hashKey'] = $this->sign($payload);

        $this->postJson('/webhooks/fawaterk/failed_json', $payload)->assertOk();

        $payment = app(StoreContext::class)->runAs($order->store_id, fn () => $payment->fresh());
        $this->assertSame(PaymentStatus::Rejected, $payment->status);
        $this->assertSame('Payment declined by issuer', $payment->rejection_reason);
        $this->assertSame(OrderStatus::PendingPayment, $this->freshOrder($order)->status);
    }

    public function test_the_webhook_also_accepts_form_encoded_bodies(): void
    {
        $order = $this->onlineOrder();
        $this->awaitingPayment($order);

        $this->post('/webhooks/fawaterk_json', $this->paidPayload($order))->assertOk();

        $this->assertSame(OrderStatus::Paid, $this->freshOrder($order)->status);
    }
}
