<?php

namespace Modules\Payments\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Auth\Models\Customer;
use Modules\Payments\Enums\PaymentStatus;
use Modules\Payments\Models\PaymentMethod;
use Modules\Payments\Testing\BuildsPayments;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Models\Order;
use Modules\Tenancy\Models\Store;
use Modules\Theming\Support\ThemeContract;
use Tests\TestCase;

class StorefrontPaymentBlockTest extends TestCase
{
    use BuildsPayments;
    use RefreshDatabase;

    private Store $store;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeReceiptDisk();
        $this->store = $this->shop('store-a');
        [$this->customer] = $this->customerWithAddress($this->store);
        $this->platformAccount(attributes: ['account_name' => 'Mawqaay Wallet', 'account_number' => '01099998888', 'instructions' => 'Send from your own wallet.']);
        $this->logInCustomer($this->store);
    }

    private function payment(Order $order, string $page = 'confirmation'): array
    {
        $props = [];
        $url = $page === 'confirmation' ? "/store-a/orders/{$order->number}/confirmation" : "/store-a/orders/{$order->number}";

        $this->get($url)->assertOk()->assertInertia(function (Assert $inertia) use (&$props, $page): void {
            $inertia->component('storefront/default/'.($page === 'confirmation' ? 'OrderConfirmation' : 'OrderDetail'))
                ->where('contractVersion', ThemeContract::VERSION);
            $props = $inertia->toArray()['props']['order']['payment'];
        });

        return $props;
    }

    public function test_an_unpaid_order_shows_where_to_transfer_how_much_and_the_reference(): void
    {
        $order = $this->orderFor($this->store, $this->customer);

        foreach (['confirmation', 'detail'] as $page) {
            $payment = $this->payment($order, $page);

            $this->assertSame('awaiting_receipt', $payment['state']);
            $this->assertSame(['name' => 'Mawqaay Wallet', 'number' => '01099998888', 'instructions' => 'Send from your own wallet.'], $payment['account']);
            $this->assertSame('1,050 EGP', $payment['amount']);
            $this->assertSame($order->number, $payment['reference']);
            $this->assertNotNull($payment['expiresAt']);
            $this->assertSame([0, 3, 3], [$payment['attemptsUsed'], $payment['attemptsLeft'], $payment['maxAttempts']]);
            $this->assertTrue($payment['canSubmit']);
            $this->assertNull($payment['rejectionReason']);
            $this->assertNull($payment['receiptUrl']);
            $this->assertSame(url("/store-a/orders/{$order->number}/receipt"), $payment['submitUrl']);
            $this->assertSame(5120, $payment['maxKilobytes']);
            $this->assertSame(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], $payment['acceptedTypes']);
        }
    }

    public function test_the_account_shown_is_the_active_one_for_the_chosen_method(): void
    {
        $this->platformAccount('instapay', ['account_name' => 'Mawqaay Pay', 'account_number' => 'mawqaay@instapay']);
        $this->platformAccount(attributes: ['account_name' => 'Old Wallet', 'is_active' => false]);
        $wallet = $this->orderFor($this->store, $this->customer);
        $instapay = $this->orderFor($this->store, $this->customer, attributes: ['payment_method' => 'instapay']);

        $this->assertSame('Mawqaay Wallet', $this->payment($wallet)['account']['name']);
        $this->assertSame('Mawqaay Pay', $this->payment($instapay)['account']['name']);
    }

    public function test_with_no_active_account_the_page_says_so_and_nothing_breaks(): void
    {
        PaymentMethod::query()->update(['is_active' => false]);
        $order = $this->orderFor($this->store, $this->customer);

        $payment = $this->payment($order);

        $this->assertNull($payment['account']);
        $this->assertSame('awaiting_receipt', $payment['state']);
    }

    public function test_an_order_keeps_showing_the_account_it_was_paid_against_after_it_is_switched_off(): void
    {
        $order = $this->orderFor($this->store, $this->customer);
        $this->paymentFor($order, attributes: ['method_snapshot' => ['name' => 'Mawqaay Wallet', 'number' => '01099998888', 'instructions' => null]]);
        PaymentMethod::query()->update(['is_active' => false, 'account_number' => '01000000000']);

        $this->assertSame('01099998888', $this->payment($order)['account']['number']);
    }

    public function test_an_order_under_review_shows_a_link_to_the_customers_own_receipt_and_stops_the_timer(): void
    {
        $order = $this->orderFor($this->store, $this->customer, OrderStatus::PaymentReview);
        $this->paymentFor($order);

        $payment = $this->payment($order);

        $this->assertSame('under_review', $payment['state']);
        $this->assertFalse($payment['canSubmit']);
        $this->assertNull($payment['expiresAt']);
        $this->assertSame(1, $payment['attemptsUsed']);
        $this->assertStringContainsString('/payment-receipts/', $payment['receiptUrl']);
        $this->assertStringContainsString('signature=', $payment['receiptUrl']);
        $this->get($payment['receiptUrl'])->assertOk();
    }

    public function test_a_rejected_order_shows_the_reason_attempts_left_and_allows_another_try(): void
    {
        $order = $this->orderFor($this->store, $this->customer);
        $this->paymentFor($order, PaymentStatus::Rejected, ['rejection_reason' => 'Amount does not match']);

        $payment = $this->payment($order);

        $this->assertSame('rejected', $payment['state']);
        $this->assertSame('Amount does not match', $payment['rejectionReason']);
        $this->assertSame([1, 2], [$payment['attemptsUsed'], $payment['attemptsLeft']]);
        $this->assertTrue($payment['canSubmit']);
    }

    public function test_after_the_third_rejection_no_more_attempts_are_offered(): void
    {
        $order = $this->orderFor($this->store, $this->customer);
        foreach ([1, 2, 3] as $attempt) {
            $this->paymentFor($order, PaymentStatus::Rejected, ['rejection_reason' => "Reason {$attempt}"]);
        }

        $payment = $this->payment($order);

        $this->assertSame('exhausted', $payment['state']);
        $this->assertSame([3, 0], [$payment['attemptsUsed'], $payment['attemptsLeft']]);
        $this->assertFalse($payment['canSubmit']);
        $this->assertSame('Reason 3', $payment['rejectionReason']);
    }

    public function test_a_paid_order_and_a_cancelled_order_offer_no_upload(): void
    {
        $paid = $this->orderFor($this->store, $this->customer, OrderStatus::Paid);
        $this->paymentFor($paid, PaymentStatus::Approved);
        $cancelled = $this->orderFor($this->store, $this->customer, OrderStatus::Cancelled);

        $paidBlock = $this->payment($paid);
        $cancelledBlock = $this->payment($cancelled);

        $this->assertSame(['paid', false, null], [$paidBlock['state'], $paidBlock['canSubmit'], $paidBlock['expiresAt']]);
        $this->assertNull($paidBlock['rejectionReason']);
        $this->assertSame(['closed', false], [$cancelledBlock['state'], $cancelledBlock['canSubmit']]);
    }

    public function test_an_order_past_its_reservation_is_not_offered_an_upload_even_before_the_job_runs(): void
    {
        $order = $this->orderFor($this->store, $this->customer, attributes: ['expires_at' => now()->subMinute()]);

        $this->assertFalse($this->payment($order)['canSubmit']);
    }

    public function test_the_block_never_reveals_duplicate_flags_or_other_customers_data(): void
    {
        $other = $this->orderFor($this->store, $this->customer);
        $first = $this->paymentFor($other, attributes: ['transfer_reference' => 'SHARED-REF']);
        $order = $this->orderFor($this->store, $this->customer);
        $this->paymentFor($order, attributes: ['transfer_reference' => 'SHARED-REF', 'duplicate_of_id' => $first->id]);

        $encoded = json_encode($this->payment($order));

        $this->assertStringNotContainsString('duplicate', strtolower($encoded));
        $this->assertStringNotContainsString($other->number, $encoded);
        $this->assertStringNotContainsString('SHARED-REF', $encoded);
    }

    public function test_the_order_list_does_not_carry_payment_details(): void
    {
        $this->orderFor($this->store, $this->customer);

        $this->get('/store-a/orders')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('storefront/default/Orders')
            ->missing('orders.0.payment'));
    }

    public function test_another_customers_order_page_is_not_found(): void
    {
        [$other] = $this->customerWithAddress($this->store, 'sara@example.com');
        $theirs = $this->orderFor($this->store, $other);

        $this->get("/store-a/orders/{$theirs->number}")->assertNotFound();
        $this->get("/store-a/orders/{$theirs->number}/confirmation")->assertNotFound();
    }
}
