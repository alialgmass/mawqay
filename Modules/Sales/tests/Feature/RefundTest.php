<?php

namespace Modules\Sales\Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Modules\Catalog\Models\Variant;
use Modules\Core\Models\AuditEntry;
use Modules\Core\Support\StoreContext;
use Modules\Ledger\Enums\LedgerAccount;
use Modules\Ledger\Services\BalanceService;
use Modules\Ledger\Testing\BuildsLedger;
use Modules\Sales\Actions\ForceOrderStatus;
use Modules\Sales\Actions\RefundOrder;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Enums\RefundReason;
use Modules\Sales\Exceptions\InvalidOrderTransition;
use Modules\Sales\Exceptions\RefundNotAllowed;
use Modules\Sales\Filament\Admin\Resources\Orders\Pages\ViewOrder as AdminViewOrder;
use Modules\Sales\Models\Order;
use Modules\Sales\Models\Refund;
use Modules\Sales\Notifications\OrderUpdate;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use BuildsLedger;
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = $this->shop('store-a');
    }

    private function actAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        Filament::setCurrentPanel('admin');

        return $admin;
    }

    /**
     * @return array<string, int>
     */
    private function books(Order $order): array
    {
        return app(BalanceService::class)->forOrder($order->id);
    }

    private function fresh(Order $order): Order
    {
        return app(StoreContext::class)->runAs($order->store_id, fn (): Order => Order::query()->findOrFail($order->id));
    }

    private function refundOnPage(Order $order, array $data)
    {
        return Livewire::test(AdminViewOrder::class, ['record' => $order->id])->callAction('refund', $data);
    }

    /**
     * The worked example: 1,200 EGP of products and 60 EGP shipping at 10% Commission.
     */
    private function posted(OrderStatus $status = OrderStatus::Paid): Order
    {
        return $this->postedOrder($this->store, [[120000, 1, 10]], 6000, $status);
    }

    public function test_a_full_refund_reverses_the_books_and_refunds_the_order(): void
    {
        Notification::fake();
        $admin = $this->actAsAdmin();
        $order = $this->posted();

        $this->refundOnPage($order, ['reason' => 'defective'])->assertHasNoActionErrors()->assertNotified(__('sales::orders.refund.done'));

        $this->assertSame(OrderStatus::Refunded, $this->fresh($order)->status);
        $this->assertSame(0, $this->books($order)[LedgerAccount::PlatformCash->value] ?? 0);
        $this->assertSame(0, $this->books($order)[LedgerAccount::PlatformCommissionRevenue->value] ?? 0);
        $this->assertSame(0, $this->books($order)[LedgerAccount::VendorPayablePending->value] ?? 0);

        $refund = app(StoreContext::class)->runAs($this->store->id, fn (): Refund => Refund::query()->firstOrFail());
        $this->assertSame(126000, $refund->amount);
        $this->assertSame(RefundReason::Defective, $refund->reason);
        $this->assertSame($admin->id, $refund->actor_id);
        $this->assertFalse($refund->restocked);

        $this->artisan('ledger:reconcile')->assertSuccessful();
    }

    public function test_a_partial_refund_takes_commission_proportionally_and_keeps_the_status(): void
    {
        $this->actAsAdmin();
        $order = $this->posted();

        $this->refundOnPage($order, ['reason' => 'wrong_size', 'amount' => 300])->assertHasNoActionErrors();

        $this->assertSame(OrderStatus::Paid, $this->fresh($order)->status);
        $books = $this->books($order);
        $this->assertSame(96000, $books[LedgerAccount::PlatformCash->value]);
        $this->assertSame(12000 - 2857, $books[LedgerAccount::PlatformCommissionRevenue->value]);
        $this->assertSame(114000 - (30000 - 2857), $books[LedgerAccount::VendorPayablePending->value]);
        $this->artisan('ledger:reconcile')->assertSuccessful();
    }

    public function test_several_partial_refunds_add_up_and_stop_at_the_amount_paid(): void
    {
        $this->actAsAdmin();
        $order = $this->posted();

        $this->refundOnPage($order, ['reason' => 'other', 'amount' => 1000])->assertHasNoActionErrors();
        $this->refundOnPage($order, ['reason' => 'other', 'amount' => 300])->assertNotified(__('sales::orders.refund.refused'));
        $this->assertSame(1, app(StoreContext::class)->runAs($this->store->id, fn (): int => Refund::query()->count()));

        $this->refundOnPage($order, ['reason' => 'other', 'amount' => 260])->assertHasNoActionErrors();

        $this->assertSame(OrderStatus::Refunded, $this->fresh($order)->status);
        $this->assertSame(0, $this->books($order)[LedgerAccount::PlatformCash->value] ?? 0);
        $this->artisan('ledger:reconcile')->assertSuccessful();
    }

    public function test_a_refund_larger_than_the_order_is_refused(): void
    {
        $order = $this->posted();

        $this->expectException(RefundNotAllowed::class);

        app(StoreContext::class)->runAs($this->store->id, fn () => app(RefundOrder::class)($order, 126001, RefundReason::Other, null, null, 'r-1'));
    }

    public function test_repeating_a_submission_refunds_once(): void
    {
        $order = $this->posted();
        $refund = fn (): Refund => app(StoreContext::class)->runAs($this->store->id, fn () => app(RefundOrder::class)($order, 30000, RefundReason::Other, null, null, 'same-ref'));

        $first = $refund();
        $second = $refund();

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, app(StoreContext::class)->runAs($this->store->id, fn (): int => Refund::query()->count()));
        $this->assertSame(96000, $this->books($order)[LedgerAccount::PlatformCash->value]);
    }

    public function test_a_refund_draws_on_available_money_after_the_funds_were_released(): void
    {
        $this->actAsAdmin();
        $order = $this->deliveredDaysAgo($this->posted(), 8);
        $this->artisan('ledger:release-funds')->assertSuccessful();
        $this->assertSame(OrderStatus::Completed, $this->fresh($order)->status);

        $this->refundOnPage($order, ['reason' => 'defective'])->assertHasActionErrors(['override_reason' => 'required']);
        $this->assertSame(OrderStatus::Completed, $this->fresh($order)->status);

        $this->refundOnPage($order, ['reason' => 'defective', 'amount' => 300, 'override_reason' => 'Customer escalated to support'])->assertHasNoActionErrors();

        $books = $this->books($order);
        $this->assertSame(96000, $books[LedgerAccount::PlatformCash->value]);
        $this->assertSame(114000 - (30000 - 2857), $books[LedgerAccount::VendorPayableAvailable->value]);
        $this->assertSame(OrderStatus::Completed, $this->fresh($order)->status);

        $audit = AuditEntry::query()->where('action', 'refund_of_completed_order')->firstOrFail();
        $this->assertSame('Customer escalated to support', $audit->reason);
        $this->assertSame(1, AuditEntry::query()->where('action', 'order_refunded')->count());
        $this->artisan('ledger:reconcile')->assertSuccessful();
    }

    public function test_orders_that_were_never_paid_or_are_already_refunded_cannot_be_refunded(): void
    {
        $this->actAsAdmin();

        foreach ([OrderStatus::Cancelled, OrderStatus::PendingPayment, OrderStatus::PaymentReview, OrderStatus::Refunded] as $status) {
            $order = $this->orderWith($this->store, status: $status);

            Livewire::test(AdminViewOrder::class, ['record' => $order->id])->assertActionHidden('refund');

            try {
                app(StoreContext::class)->runAs($this->store->id, fn () => app(RefundOrder::class)($order, null, RefundReason::Other, null, null, 'x-'.$status->value));
                $this->fail("{$status->value} must not be refundable.");
            } catch (RefundNotAllowed) {
                $this->assertSame(0, app(StoreContext::class)->runAs($this->store->id, fn (): int => Refund::query()->count()));
            }
        }
    }

    public function test_a_refund_can_be_made_from_every_status_between_paid_and_delivered(): void
    {
        foreach ([OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered] as $status) {
            $order = $this->postedOrder($this->store, [[100000, 1, 10]], 0, $status);

            $refund = app(StoreContext::class)->runAs($this->store->id, fn () => app(RefundOrder::class)($order, null, RefundReason::NotDelivered, null, null, 'r-'.$status->value));

            $this->assertSame(100000, $refund->amount);
            $this->assertSame(OrderStatus::Refunded, $this->fresh($order)->status);
        }

        $this->artisan('ledger:reconcile')->assertSuccessful();
    }

    public function test_a_refunded_order_is_never_released_to_the_vendor(): void
    {
        $order = $this->deliveredDaysAgo($this->posted(), 8);
        app(StoreContext::class)->runAs($this->store->id, fn () => app(RefundOrder::class)($order, null, RefundReason::Other, null, null, 'r-1'));

        $this->artisan('ledger:release-funds')->assertSuccessful();

        $this->assertSame(OrderStatus::Refunded, $this->fresh($order)->status);
        $this->assertSame(0, $this->books($order)[LedgerAccount::VendorPayableAvailable->value] ?? 0);
    }

    public function test_restocking_returns_all_goods_on_a_full_refund_and_none_when_off(): void
    {
        $this->actAsAdmin();
        $withRestock = $this->postedOrder($this->store, [[100000, 2, 10]]);
        $without = $this->postedOrder($this->store, [[100000, 2, 10]]);
        $variantId = app(StoreContext::class)->runAs($this->store->id, fn () => $withRestock->items()->firstOrFail()->variant_id);
        $stock = fn (): int => app(StoreContext::class)->runAs($this->store->id, fn (): int => Variant::query()->findOrFail($variantId)->stock);
        $before = $stock();

        $this->refundOnPage($without, ['reason' => 'other'])->assertHasNoActionErrors();
        $this->assertSame($before, $stock());

        $this->refundOnPage($withRestock, ['reason' => 'other', 'restock' => true])->assertHasNoActionErrors();
        $this->assertSame($before + 2, $stock());
        $this->assertTrue(app(StoreContext::class)->runAs($this->store->id, fn (): bool => Refund::query()->where('order_id', $withRestock->id)->firstOrFail()->restocked));
    }

    public function test_a_partial_refund_restocks_only_the_chosen_quantities(): void
    {
        $this->actAsAdmin();
        $order = $this->postedOrder($this->store, [[100000, 3, 10]]);
        $item = app(StoreContext::class)->runAs($this->store->id, fn () => $order->items()->firstOrFail());
        $stock = fn (): int => app(StoreContext::class)->runAs($this->store->id, fn (): int => Variant::query()->findOrFail($item->variant_id)->stock);
        $before = $stock();

        $this->refundOnPage($order, ['reason' => 'wrong_size', 'amount' => 1000, 'restock' => true, 'restock_'.$item->id => 1])->assertNotified(__('sales::orders.refund.done'));
        $this->assertSame($before + 1, $stock());

        $this->refundOnPage($order, ['reason' => 'wrong_size', 'amount' => 500, 'restock' => true, 'restock_'.$item->id => 3])->assertNotified(__('sales::orders.refund.refused'));
        $this->assertSame($before + 1, $stock());
    }

    public function test_a_partial_refund_with_restock_needs_quantities(): void
    {
        $order = $this->postedOrder($this->store, [[100000, 3, 10]]);

        $this->expectException(RefundNotAllowed::class);

        app(StoreContext::class)->runAs($this->store->id, fn () => app(RefundOrder::class)($order, 1000, RefundReason::Other, null, null, 'r-1', restock: true));
    }

    public function test_the_customer_and_the_vendor_are_told_about_a_refund(): void
    {
        Notification::fake();
        $owner = User::factory()->vendorOwner($this->store)->create();
        $order = $this->posted();

        app(StoreContext::class)->runAs($this->store->id, fn () => app(RefundOrder::class)($order, 30000, RefundReason::Other, null, null, 'r-1'));

        Notification::assertSentTo(app(StoreContext::class)->runAs($this->store->id, fn () => $this->fresh($order)->customer), OrderUpdate::class, fn (OrderUpdate $n): bool => $n->kind === 'refunded');
        Notification::assertSentTo($owner, OrderUpdate::class, fn (OrderUpdate $n): bool => $n->kind === 'vendor_refunded');
    }

    public function test_only_a_platform_admin_may_refund(): void
    {
        $order = $this->posted();
        $owner = User::factory()->vendorOwner($this->store)->create();

        $this->assertTrue(Gate::forUser(User::factory()->admin()->create())->allows('refund', $order));
        $this->assertTrue(Gate::forUser($owner)->denies('refund', $order));
    }

    public function test_forcing_a_status_needs_a_reason_leaves_an_audit_entry_and_stamps_delivery(): void
    {
        $admin = $this->actAsAdmin();
        $order = $this->orderWith($this->store, status: OrderStatus::Shipped);

        Livewire::test(AdminViewOrder::class, ['record' => $order->id])->callAction('forceStatus', ['status' => 'delivered', 'reason' => ''])->assertHasActionErrors(['reason' => 'required']);
        $this->assertSame(OrderStatus::Shipped, $this->fresh($order)->status);

        Livewire::test(AdminViewOrder::class, ['record' => $order->id])->callAction('forceStatus', ['status' => 'delivered', 'reason' => 'Courier confirmed by phone'])->assertHasNoActionErrors();

        $forced = $this->fresh($order);
        $this->assertSame(OrderStatus::Delivered, $forced->status);
        $this->assertNotNull($forced->delivered_at);

        $audit = AuditEntry::query()->where('action', 'order_status_forced')->firstOrFail();
        $this->assertSame('Courier confirmed by phone', $audit->reason);
        $this->assertSame($admin->id, $audit->actor_id);
        $this->assertSame(['status' => 'shipped'], $audit->before);
        $this->assertSame(['status' => 'delivered'], $audit->after);
        app(StoreContext::class)->runAs($this->store->id, fn () => $this->assertStringStartsWith('Forced:', $forced->statusLogs()->get()->last()->note));
    }

    public function test_a_forced_change_cannot_create_a_refund_or_a_payment(): void
    {
        $order = $this->orderWith($this->store, status: OrderStatus::Delivered);

        foreach ([OrderStatus::Refunded, OrderStatus::Paid, OrderStatus::Completed] as $target) {
            try {
                app(StoreContext::class)->runAs($this->store->id, fn () => app(ForceOrderStatus::class)($order, $target, 'because', null));
                $this->fail("Forcing {$target->value} must be refused.");
            } catch (RefundNotAllowed) {
                $this->assertSame(OrderStatus::Delivered, $this->fresh($order)->status);
            }
        }

        $this->assertSame(0, AuditEntry::query()->count());
    }

    public function test_a_forced_change_still_obeys_the_state_machine(): void
    {
        $order = $this->orderWith($this->store, status: OrderStatus::Paid);

        $this->expectException(InvalidOrderTransition::class);

        app(StoreContext::class)->runAs($this->store->id, fn () => app(ForceOrderStatus::class)($order, OrderStatus::Delivered, 'skip', null));
    }

    public function test_refunds_and_overrides_belong_to_their_store(): void
    {
        $other = $this->shop('store-b');
        $order = $this->posted();
        app(StoreContext::class)->runAs($this->store->id, fn () => app(RefundOrder::class)($order, 30000, RefundReason::Other, null, null, 'r-1'));

        $this->assertSame(1, app(StoreContext::class)->runAs($this->store->id, fn (): int => Refund::query()->count()));
        $this->assertSame(0, app(StoreContext::class)->runAs($other->id, fn (): int => Refund::query()->count()));
    }
}
