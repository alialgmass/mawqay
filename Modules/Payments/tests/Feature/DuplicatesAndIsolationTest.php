<?php

namespace Modules\Payments\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Auth\Models\Customer;
use Modules\Core\Support\StoreContext;
use Modules\Payments\Contracts\PaymentGateway;
use Modules\Payments\Enums\PaymentStatus;
use Modules\Payments\Gateways\ManualPaymentGateway;
use Modules\Payments\Models\Payment;
use Modules\Payments\Support\ReferenceFingerprinter;
use Modules\Payments\Testing\BuildsPayments;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Models\Order;
use Modules\Tenancy\Models\Store;
use Modules\Tenancy\Testing\AssertsStoreIsolation;
use Tests\TestCase;

class DuplicatesAndIsolationTest extends TestCase
{
    use AssertsStoreIsolation;
    use BuildsPayments;
    use RefreshDatabase;

    private Store $storeA;

    private Store $storeB;

    private Customer $customerA;

    private Customer $customerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeReceiptDisk();
        $this->platformAccount();
        $this->storeA = $this->shop('store-a');
        $this->storeB = $this->shop('store-b');
        [$this->customerA] = $this->customerWithAddress($this->storeA, 'mona@example.com');
        [$this->customerB] = $this->customerWithAddress($this->storeB, 'nour@example.com');
    }

    private function submit(Store $store, Order $order, ?string $reference): void
    {
        $this->logInCustomer($store, $store->is($this->storeA) ? 'mona@example.com' : 'nour@example.com');
        $this->post("/{$store->slug}/orders/{$order->number}/receipt", ['receipt' => $this->image(), 'transfer_reference' => $reference], ['Accept' => 'application/json'])->assertRedirect();
    }

    private function paymentOf(Order $order): Payment
    {
        return Payment::query()->withoutGlobalScopes()->where('order_id', $order->id)->latest('id')->firstOrFail();
    }

    public function test_a_reference_reused_in_another_store_is_flagged_on_the_later_payment_without_blocking_it(): void
    {
        $orderA = $this->orderFor($this->storeA, $this->customerA);
        $orderB = $this->orderFor($this->storeB, $this->customerB);

        $this->submit($this->storeA, $orderA, 'TX-778899');
        $this->submit($this->storeB, $orderB, ' tx 77_88 99 ');

        $first = $this->paymentOf($orderA);
        $second = $this->paymentOf($orderB);
        $this->assertNull($first->duplicate_of_id);
        $this->assertSame($first->id, $second->duplicate_of_id);
        $this->assertTrue($second->isDuplicate());
        $this->assertSame(PaymentStatus::PendingReview, $second->status);
        $this->assertSame(OrderStatus::PaymentReview, Order::query()->withoutGlobalScopes()->find($orderB->id)->status);
    }

    public function test_a_reference_reused_in_the_same_store_is_flagged_too(): void
    {
        $one = $this->orderFor($this->storeA, $this->customerA);
        $two = $this->orderFor($this->storeA, $this->customerA);

        $this->submit($this->storeA, $one, 'REF-1');
        $this->submit($this->storeA, $two, 'ref-1');

        $this->assertSame($this->paymentOf($one)->id, $this->paymentOf($two)->duplicate_of_id);
    }

    public function test_different_or_missing_references_are_never_flagged(): void
    {
        $orders = [$this->orderFor($this->storeA, $this->customerA), $this->orderFor($this->storeA, $this->customerA), $this->orderFor($this->storeA, $this->customerA), $this->orderFor($this->storeA, $this->customerA)];

        $this->submit($this->storeA, $orders[0], 'REF-1');
        $this->submit($this->storeA, $orders[1], 'REF-2');
        $this->submit($this->storeA, $orders[2], null);
        $this->submit($this->storeA, $orders[3], '  ---  ');

        foreach ($orders as $order) {
            $this->assertNull($this->paymentOf($order)->duplicate_of_id);
        }
    }

    public function test_spellings_of_one_reference_compare_equal(): void
    {
        $this->assertSame(ReferenceFingerprinter::fingerprint('TX-778899'), ReferenceFingerprinter::fingerprint(' tx 77 88_99 '));
        $this->assertNotSame(ReferenceFingerprinter::fingerprint('TX-778899'), ReferenceFingerprinter::fingerprint('TX-778898'));
        $this->assertNull(ReferenceFingerprinter::fingerprint(' - _ '));
    }

    public function test_the_duplicate_index_holds_only_fingerprints_and_reveals_nothing_across_stores(): void
    {
        $orderA = $this->orderFor($this->storeA, $this->customerA);
        $orderB = $this->orderFor($this->storeB, $this->customerB);
        $this->submit($this->storeA, $orderA, 'SECRET-TRANSFER-REF');
        $this->submit($this->storeB, $orderB, 'secret transfer ref');
        $first = $this->paymentOf($orderA);

        $this->assertSame(['id', 'fingerprint', 'first_payment_id', 'created_at'], Schema::getColumnListing('payment_reference_fingerprints'));
        $row = DB::table('payment_reference_fingerprints')->sole();
        $this->assertSame(hash('sha256', 'secrettransferref'), $row->fingerprint);
        $this->assertSame($first->id, $row->first_payment_id);
        $this->assertStringNotContainsStringIgnoringCase('secret', json_encode($row));

        app(StoreContext::class)->runAs($this->storeB->id, function () use ($first): void {
            $this->assertNull(Payment::query()->find($first->id), 'Store B must not read Store A\'s payment');
            $this->assertSame(1, Payment::query()->count());
        });

        $this->logInCustomer($this->storeB, 'nour@example.com');
        $this->get("/store-b/orders/{$orderB->number}")->assertOk()->assertInertia(function (Assert $page) use ($orderA): void {
            $encoded = json_encode($page->toArray()['props']);
            $this->assertStringNotContainsString($orderA->number, $encoded);
            $this->assertStringNotContainsStringIgnoringCase('duplicate', $encoded);
            $this->assertStringNotContainsString('SECRET-TRANSFER-REF', $encoded);
        });
    }

    public function test_payments_are_isolated_between_stores(): void
    {
        $this->assertModelIsIsolatedBetweenStores(Payment::class, fn (Store $store): Payment => Payment::factory()->create(['store_id' => $store->id]));
    }

    public function test_platform_accounts_and_the_duplicate_index_belong_to_no_store(): void
    {
        $this->assertFalse(Schema::hasColumn('payment_methods', 'store_id'));
        $this->assertFalse(Schema::hasColumn('payment_reference_fingerprints', 'store_id'));
        $this->assertTrue(Schema::hasColumn('payments', 'store_id'));
    }

    public function test_the_manual_gateway_records_a_claim_and_awaits_review(): void
    {
        $gateway = app(PaymentGateway::class);
        $order = $this->orderFor($this->storeA, $this->customerA);

        $payment = $gateway->submit($order, $this->image(), 'REF-9', '01012345678');

        $this->assertInstanceOf(ManualPaymentGateway::class, $gateway);
        $this->assertSame(PaymentStatus::PendingReview, $payment->status);
        $this->assertSame(OrderStatus::PaymentReview, Order::query()->withoutGlobalScopes()->find($order->id)->status);
    }
}
