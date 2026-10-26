<?php

namespace Modules\Payments\Tests\Feature;

use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Modules\Auth\Models\Customer;
use Modules\Catalog\Models\Variant;
use Modules\Core\Support\PlatformSettings;
use Modules\Core\Support\StoreContext;
use Modules\Payments\Actions\ApprovePayment;
use Modules\Payments\Actions\RejectPayment;
use Modules\Payments\Enums\PaymentStatus;
use Modules\Payments\Events\PaymentApproved;
use Modules\Payments\Exceptions\PaymentCannotBeReviewed;
use Modules\Payments\Filament\Admin\Resources\Payments\Pages\ListPayments;
use Modules\Payments\Filament\Admin\Resources\Payments\PaymentResource;
use Modules\Payments\Models\Payment;
use Modules\Payments\Notifications\OrderWasPaid;
use Modules\Payments\Notifications\PaymentWasApproved;
use Modules\Payments\Notifications\PaymentWasRejected;
use Modules\Payments\Testing\BuildsPayments;
use Modules\Sales\Actions\CancelOrder;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Models\Order;
use Modules\Sales\Models\OrderStatusLog;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class ReviewPaymentTest extends TestCase
{
    use BuildsPayments;
    use RefreshDatabase;

    private Store $store;

    private Customer $customer;

    private Variant $variant;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeReceiptDisk();
        $this->store = $this->shop('store-a', ['name' => 'Alpha']);
        [$this->customer] = $this->customerWithAddress($this->store);
        $this->variant = $this->variant($this->store, stock: 10);
        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
        Filament::setCurrentPanel('admin');
    }

    /**
     * An Order in review with its Receipt, holding two units of Stock.
     */
    private function underReview(array $payment = [], ?Store $store = null, ?Customer $customer = null): Payment
    {
        $store ??= $this->store;
        $order = $this->orderFor($store, $customer ?? $this->customer, OrderStatus::PaymentReview, $store->is($this->store) ? $this->variant : null, 2);

        return $this->paymentFor($order, attributes: $payment);
    }

    private function order(Payment $payment): Order
    {
        return Order::query()->withoutGlobalScopes()->findOrFail($payment->order_id);
    }

    private function fresh(Payment $payment): Payment
    {
        return Payment::query()->withoutGlobalScopes()->findOrFail($payment->id);
    }

    private function stock(): array
    {
        $variant = Variant::query()->withoutGlobalScopes()->findOrFail($this->variant->id);

        return ['stock' => $variant->stock, 'reserved' => $variant->reserved];
    }

    // ----- Queue -----

    public function test_the_queue_lists_waiting_receipts_oldest_first_and_hides_decided_ones(): void
    {
        $newest = $this->underReview(['created_at' => now()->subHour()]);
        $oldest = $this->underReview(['created_at' => now()->subDays(2)]);
        $middle = $this->underReview(['created_at' => now()->subHours(5)]);
        $approved = $this->paymentFor($this->orderFor($this->store, $this->customer, OrderStatus::Paid), PaymentStatus::Approved);

        Livewire::test(ListPayments::class)
            ->assertCanSeeTableRecords([$oldest, $middle, $newest], inOrder: true)
            ->assertCanNotSeeTableRecords([$approved])
            ->removeTableFilter('status')
            ->assertCanSeeTableRecords([$approved]);
    }

    public function test_the_menu_badge_counts_the_receipts_waiting(): void
    {
        $this->assertNull(PaymentResource::getNavigationBadge());

        $this->underReview();
        $this->underReview();
        $this->paymentFor($this->orderFor($this->store, $this->customer, OrderStatus::Paid), PaymentStatus::Approved);

        $this->assertSame('2', PaymentResource::getNavigationBadge());
    }

    public function test_the_queue_puts_the_order_total_beside_the_amount_to_transfer_with_the_payment_details(): void
    {
        $payment = $this->underReview(['amount' => 99000, 'transfer_reference' => 'TX-1', 'sender_phone' => '+201012345678', 'attempt' => 2]);

        Livewire::test(ListPayments::class)
            ->assertTableColumnFormattedStateSet('order.total', '250 EGP', $payment)
            ->assertTableColumnFormattedStateSet('amount', '990 EGP', $payment)
            ->assertTableColumnStateSet('transfer_reference', 'TX-1', $payment)
            ->assertTableColumnStateSet('sender_phone', '+201012345678', $payment)
            ->assertTableColumnStateSet('attempt', 2, $payment)
            ->assertTableColumnStateSet('store.name', 'Alpha', $payment)
            ->assertTableColumnFormattedStateSet('method', __('sales::orders.payment_methods.vodafone_cash'), $payment);
    }

    public function test_a_reused_transfer_reference_is_flagged_and_still_reviewable(): void
    {
        $first = $this->underReview();
        $second = $this->underReview(['duplicate_of_id' => $first->id]);

        Livewire::test(ListPayments::class)
            ->assertTableColumnStateSet('duplicate_of_id', null, $first)
            ->assertTableColumnStateSet('duplicate_of_id', __('payments::review.payment.duplicate'), $second)
            ->assertActionVisible(TestAction::make('approve')->table($second));
    }

    public function test_only_platform_admins_open_the_queue(): void
    {
        $this->actingAs(User::factory()->vendorOwner($this->store)->create());

        $this->get('/admin/payments')->assertForbidden();
    }

    // ----- Approve -----

    public function test_approving_pays_the_order_commits_stock_and_records_the_decision(): void
    {
        Event::fake([PaymentApproved::class]);
        Notification::fake();
        $payment = $this->underReview();

        Livewire::test(ListPayments::class)
            ->callAction(TestAction::make('approve')->table($payment))
            ->assertNotified(__('payments::review.actions.approved'));

        $payment = $this->fresh($payment);
        $this->assertSame(PaymentStatus::Approved, $payment->status);
        $this->assertSame($this->admin->id, $payment->reviewed_by);
        $this->assertNotNull($payment->reviewed_at);
        $this->assertSame(OrderStatus::Paid, $this->order($payment)->status);
        $this->assertSame(['stock' => 8, 'reserved' => 0], $this->stock());

        $log = OrderStatusLog::query()->withoutGlobalScopes()->where('order_id', $payment->order_id)->latest('id')->firstOrFail();
        $this->assertSame(['payment_review', 'paid', 'admin', $this->admin->id], [$log->from_status->value, $log->to_status->value, $log->actor_type, $log->actor_id]);

        Event::assertDispatchedTimes(PaymentApproved::class, 1);
        Notification::assertSentTo($this->customer, PaymentWasApproved::class);
    }

    public function test_approving_twice_changes_and_announces_nothing_the_second_time(): void
    {
        Event::fake([PaymentApproved::class]);
        Notification::fake();
        $payment = $this->underReview();

        Livewire::test(ListPayments::class)->callAction(TestAction::make('approve')->table($payment));
        app(ApprovePayment::class)($this->fresh($payment), $this->admin->id);
        app(ApprovePayment::class)($payment, $this->admin->id);

        Event::assertDispatchedTimes(PaymentApproved::class, 1);
        Notification::assertSentToTimes($this->customer, PaymentWasApproved::class, 1);
        $this->assertSame(['stock' => 8, 'reserved' => 0], $this->stock());
        $this->assertSame(2, OrderStatusLog::query()->withoutGlobalScopes()->where('order_id', $payment->order_id)->count());
    }

    public function test_the_vendor_owner_is_told_of_the_paid_order_in_the_app_only(): void
    {
        Notification::fake();
        $owner = User::factory()->vendorOwner($this->store)->create();
        $otherOwner = User::factory()->vendorOwner($this->shop('store-b'))->create();
        $payment = $this->underReview();

        app(ApprovePayment::class)($payment, $this->admin->id);

        Notification::assertSentTo($owner, OrderWasPaid::class, function (OrderWasPaid $notification) use ($owner): bool {
            $this->assertSame(['database'], $notification->via($owner));

            return true;
        });
        Notification::assertNotSentTo($otherOwner, OrderWasPaid::class);
        Notification::assertNotSentTo($this->admin, OrderWasPaid::class);
    }

    public function test_the_notifications_are_in_arabic_and_stored_in_the_database(): void
    {
        $payment = $this->underReview();
        $owner = User::factory()->vendorOwner($this->store)->create();

        app(ApprovePayment::class)($payment, $this->admin->id);

        $number = $this->order($payment)->number;
        $this->assertSame(__('payments::notifications.approved.body', ['number' => $number], 'ar'), $this->customer->notifications()->sole()->data['body']);
        $this->assertSame(__('payments::notifications.order_paid.body', ['number' => $number], 'ar'), $owner->notifications()->sole()->data['body']);
        $this->assertStringContainsString($number, $this->customer->notifications()->sole()->data['body']);
    }

    public function test_an_order_cancelled_while_waiting_cannot_be_approved_or_rejected(): void
    {
        Event::fake([PaymentApproved::class]);
        $payment = $this->underReview();
        app(CancelOrder::class)($this->order($payment), 'admin', $this->admin->id, 'Customer asked');
        $stockAfterCancel = $this->stock();

        Livewire::test(ListPayments::class)
            ->assertActionHidden(TestAction::make('approve')->table($payment))
            ->assertActionHidden(TestAction::make('reject')->table($payment));

        foreach ([
            fn () => app(ApprovePayment::class)($payment, $this->admin->id),
            fn () => app(RejectPayment::class)($payment, $this->admin->id, 'No'),
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('A cancelled Order must refuse the decision.');
            } catch (PaymentCannotBeReviewed) {
                $this->assertTrue(true);
            }
        }

        $this->assertSame(PaymentStatus::PendingReview, $this->fresh($payment)->status);
        $this->assertSame(OrderStatus::Cancelled, $this->order($payment)->status);
        $this->assertSame($stockAfterCancel, $this->stock());
        Event::assertNotDispatched(PaymentApproved::class);
    }

    // ----- Reject -----

    public function test_rejecting_needs_a_reason(): void
    {
        $payment = $this->underReview();

        Livewire::test(ListPayments::class)
            ->callAction(TestAction::make('reject')->table($payment), ['reason' => ''])
            ->assertHasActionErrors(['reason' => 'required']);

        $this->assertSame(PaymentStatus::PendingReview, $this->fresh($payment)->status);
        $this->assertSame(OrderStatus::PaymentReview, $this->order($payment)->status);
    }

    public function test_rejecting_returns_the_order_to_payment_on_the_shorter_window_and_tells_the_customer(): void
    {
        Notification::fake();
        $payment = $this->underReview();
        $this->order($payment)->forceFill(['expires_at' => now()->subDays(3)])->save();

        Livewire::test(ListPayments::class)
            ->callAction(TestAction::make('reject')->table($payment), ['reason' => 'The amount is wrong'])
            ->assertHasNoActionErrors()
            ->assertNotified(__('payments::review.actions.rejected'));

        $payment = $this->fresh($payment);
        $order = $this->order($payment);
        $this->assertSame(PaymentStatus::Rejected, $payment->status);
        $this->assertSame('The amount is wrong', $payment->rejection_reason);
        $this->assertSame($this->admin->id, $payment->reviewed_by);
        $this->assertNotNull($payment->reviewed_at);
        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertEqualsWithDelta(now()->addHours(app(PlatformSettings::class)->paymentRetryWindowHours())->timestamp, $order->expires_at->timestamp, 5);
        $this->assertLessThan(now()->addHours(app(PlatformSettings::class)->stockReservationHours())->timestamp, $order->expires_at->timestamp);
        $this->assertSame(['stock' => 10, 'reserved' => 2], $this->stock());

        Notification::assertSentTo($this->customer, PaymentWasRejected::class, fn (PaymentWasRejected $notification): bool => $notification->reason === 'The amount is wrong');
    }

    public function test_rejecting_twice_changes_and_notifies_nothing_the_second_time(): void
    {
        Notification::fake();
        $payment = $this->underReview();

        app(RejectPayment::class)($payment, $this->admin->id, 'First');
        app(RejectPayment::class)($payment, $this->admin->id, 'Second');

        Notification::assertSentToTimes($this->customer, PaymentWasRejected::class, 1);
        $this->assertSame('First', $this->fresh($payment)->rejection_reason);
    }

    public function test_a_rejected_payment_cannot_later_be_approved(): void
    {
        Event::fake([PaymentApproved::class]);
        $payment = $this->underReview();
        app(RejectPayment::class)($payment, $this->admin->id, 'Blurry');

        $this->expectException(PaymentCannotBeReviewed::class);

        try {
            app(ApprovePayment::class)($this->fresh($payment), $this->admin->id);
        } finally {
            Event::assertNotDispatched(PaymentApproved::class);
            $this->assertSame(OrderStatus::PendingPayment, $this->order($payment)->status);
        }
    }

    public function test_after_each_rejection_the_customer_can_resubmit_until_the_third_is_rejected(): void
    {
        $order = $this->orderFor($this->store, $this->customer, OrderStatus::PendingPayment, $this->variant);
        $this->logInCustomer($this->store);
        $send = fn () => $this->post("/store-a/orders/{$order->number}/receipt", ['receipt' => $this->image()], ['Accept' => 'application/json']);
        $status = fn (): OrderStatus => Order::query()->withoutGlobalScopes()->findOrFail($order->id)->status;
        $latest = fn (): Payment => Payment::query()->withoutGlobalScopes()->where('order_id', $order->id)->latest('attempt')->firstOrFail();

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $send()->assertRedirect();
            $this->assertSame(OrderStatus::PaymentReview, $status());

            app(RejectPayment::class)($latest(), $this->admin->id, "Try {$attempt}");
            $this->assertSame(OrderStatus::PendingPayment, $status());
        }

        $send()->assertStatus(422)->assertJsonValidationErrors('order');

        $this->assertSame(3, Payment::query()->withoutGlobalScopes()->where('order_id', $order->id)->count());
        $this->assertSame(OrderStatus::PendingPayment, $status());
        $this->assertSame(['stock' => 10, 'reserved' => 2], $this->stock());
    }

    public function test_the_admin_can_still_cancel_an_order_whose_third_receipt_was_rejected(): void
    {
        $order = $this->orderFor($this->store, $this->customer, OrderStatus::PaymentReview, $this->variant);
        foreach (['One', 'Two'] as $reason) {
            $this->paymentFor($order, PaymentStatus::Rejected);
        }
        $third = $this->paymentFor($order);
        app(RejectPayment::class)($third, $this->admin->id, 'Three');

        $this->assertSame(OrderStatus::PendingPayment, $this->order($third)->status);

        app(CancelOrder::class)($this->order($third), 'admin', $this->admin->id, 'No attempts left');

        $this->assertSame(OrderStatus::Cancelled, $this->order($third)->status);
        $this->assertSame(['stock' => 10, 'reserved' => 0], $this->stock());
    }

    // ----- Expiry interplay -----

    public function test_an_order_under_review_is_not_expired_but_a_rejected_one_expires_on_the_shorter_window(): void
    {
        $payment = $this->underReview();
        $this->order($payment)->forceFill(['expires_at' => now()->subDay()])->save();

        Artisan::call('orders:expire');

        $this->assertSame(OrderStatus::PaymentReview, $this->order($payment)->status);
        $this->assertSame(['stock' => 10, 'reserved' => 2], $this->stock());

        app(RejectPayment::class)($payment, $this->admin->id, 'Unclear');
        Artisan::call('orders:expire');
        $this->assertSame(OrderStatus::PendingPayment, $this->order($payment)->status);

        $this->travel(app(PlatformSettings::class)->paymentRetryWindowHours() + 1)->hours();
        Artisan::call('orders:expire');

        $this->assertSame(OrderStatus::Cancelled, $this->order($payment)->status);
        $this->assertSame(['stock' => 10, 'reserved' => 0], $this->stock());
    }

    public function test_decisions_run_inside_the_orders_own_store(): void
    {
        $storeB = $this->shop('store-b');
        [$customerB] = $this->customerWithAddress($storeB, 'nour@example.com');
        $payment = $this->underReview(store: $storeB, customer: $customerB);

        app(ApprovePayment::class)($payment, $this->admin->id);

        $this->assertSame(OrderStatus::Paid, $this->order($payment)->status);
        $this->assertNull(app(StoreContext::class)->id());
    }
}
