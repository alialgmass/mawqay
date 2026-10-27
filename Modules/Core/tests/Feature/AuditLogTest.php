<?php

namespace Modules\Core\Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use LogicException;
use Modules\Backoffice\Filament\Admin\Resources\AuditEntries\Pages\ListAuditEntries;
use Modules\Catalog\Models\Variant;
use Modules\Core\Models\AuditEntry;
use Modules\Core\Support\Audit;
use Modules\Core\Support\PlatformSettings;
use Modules\Core\Support\StoreContext;
use Modules\Ledger\Actions\RequestPayout;
use Modules\Ledger\Actions\SettlePayout;
use Modules\Ledger\Enums\PayoutMethod;
use Modules\Ledger\Models\PayoutDetail;
use Modules\Ledger\Testing\BuildsLedger;
use Modules\Payments\Actions\ApprovePayment;
use Modules\Payments\Actions\RejectPayment;
use Modules\Payments\Enums\PaymentStatus;
use Modules\Payments\Models\Payment;
use Modules\Sales\Actions\ForceOrderStatus;
use Modules\Sales\Actions\RefundOrder;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Enums\RefundReason;
use Modules\Sales\Exceptions\InvalidOrderTransition;
use Modules\Sales\Exceptions\RefundNotAllowed;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use BuildsLedger;
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = $this->shop('store-a');
    }

    private function entries(?string $action = null)
    {
        return AuditEntry::query()->when($action, fn ($query) => $query->where('action', $action))->get();
    }

    private function inStore(callable $callback): mixed
    {
        return app(StoreContext::class)->runAs($this->store->id, $callback);
    }

    public function test_an_entry_records_who_what_and_before_and_after(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Audit::record('something_changed', $this->store, ['rate' => 10], ['rate' => 12], 'Agreed terms', $this->store->id);

        $entry = $this->entries()->sole();
        $this->assertSame($admin->id, $entry->actor_id);
        $this->assertSame('admin', $entry->actor_role);
        $this->assertSame($this->store->id, $entry->store_id);
        $this->assertSame(Store::class, $entry->subject_type);
        $this->assertSame($this->store->id, $entry->subject_id);
        $this->assertSame(['rate' => 10], $entry->before);
        $this->assertSame(['rate' => 12], $entry->after);
        $this->assertSame('Agreed terms', $entry->reason);
    }

    public function test_entries_cannot_be_changed_or_removed(): void
    {
        $entry = Audit::record('x', $this->store);

        foreach ([
            fn () => $entry->update(['action' => 'y']),
            fn () => $entry->delete(),
            fn () => AuditEntry::query()->update(['action' => 'y']),
            fn () => AuditEntry::query()->delete(),
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('An audit entry was altered.');
            } catch (LogicException $exception) {
                $this->assertStringContainsString('append-only', $exception->getMessage());
            }
        }

        $this->assertSame('x', $this->entries()->sole()->action);
    }

    public function test_a_commission_rate_or_slug_change_is_audited_and_other_changes_are_not(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->store->update(['name' => 'Renamed']);
        $this->assertCount(0, $this->entries());

        $this->store->update(['commission_rate' => '12.50']);
        $this->store->update(['slug' => 'store-z']);

        $rate = $this->entries('store_commission_rate_changed')->sole();
        $this->assertSame(['commission_rate' => null], $rate->before);
        $this->assertSame(['commission_rate' => '12.50'], $rate->after);
        $slug = $this->entries('store_slug_changed')->sole();
        $this->assertSame(['slug' => 'store-a'], $slug->before);
        $this->assertSame(['slug' => 'store-z'], $slug->after);
    }

    public function test_a_platform_setting_change_records_only_what_changed(): void
    {
        $settings = app(PlatformSettings::class);

        $settings->update(['default_commission_rate' => 10, 'survey_delay_days' => 2]);
        $this->assertCount(0, $this->entries(), 'Saving the same values is not a change.');

        $settings->update(['default_commission_rate' => 15, 'survey_delay_days' => 2]);

        $entry = $this->entries('platform_settings_changed')->sole();
        $this->assertSame(['default_commission_rate' => 10], $entry->before);
        $this->assertSame(['default_commission_rate' => 15], $entry->after);
    }

    public function test_approving_and_rejecting_a_payment_are_audited_with_the_reviewer(): void
    {
        $admin = User::factory()->admin()->create();
        $approved = $this->pendingPayment('a@example.com');
        $rejected = $this->pendingPayment('b@example.com');

        app(ApprovePayment::class)($approved, $admin->id);
        app(RejectPayment::class)($rejected, $admin->id, 'Blurry receipt');

        $yes = $this->entries('payment_approved')->sole();
        $this->assertSame($admin->id, $yes->actor_id);
        $this->assertSame($approved->id, $yes->subject_id);
        $this->assertSame(['status' => 'pending_review'], $yes->before);
        $this->assertSame('approved', $yes->after['status']);
        $no = $this->entries('payment_rejected')->sole();
        $this->assertSame('Blurry receipt', $no->reason);
        $this->assertSame($rejected->id, $no->subject_id);
    }

    public function test_approving_twice_audits_once(): void
    {
        $admin = User::factory()->admin()->create();
        $payment = $this->pendingPayment('a@example.com');

        app(ApprovePayment::class)($payment, $admin->id);
        app(ApprovePayment::class)($payment, $admin->id);

        $this->assertCount(1, $this->entries('payment_approved'));
    }

    public function test_a_refund_is_audited_with_the_amount_and_reason(): void
    {
        $order = $this->postedOrder($this->store, [[120000, 1, 10]], 6000);
        $this->inStore(fn () => app(RefundOrder::class)($order, 30000, RefundReason::Defective, 'Torn seam', 9, 'r-1'));

        $entry = $this->entries('order_refunded')->sole();
        $this->assertSame(9, $entry->actor_id);
        $this->assertSame(['refunded_total' => 0], $entry->before);
        $this->assertSame(30000, $entry->after['amount']);
        $this->assertSame(30000, $entry->after['refunded_total']);
        $this->assertSame('defective: Torn seam', $entry->reason);
    }

    public function test_a_refused_refund_leaves_no_entry(): void
    {
        $order = $this->postedOrder($this->store, [[100000, 1, 10]]);

        try {
            $this->inStore(fn () => app(RefundOrder::class)($order, 999999, RefundReason::Other, null, 9, 'r-1'));
            $this->fail('The refund must be refused.');
        } catch (RefundNotAllowed) {
            $this->assertCount(0, $this->entries());
        }
    }

    public function test_a_forced_status_change_is_audited_and_a_refused_one_is_not(): void
    {
        $order = $this->orderWith($this->store, status: OrderStatus::Shipped);

        $this->inStore(fn () => app(ForceOrderStatus::class)($order, OrderStatus::Delivered, 'Courier confirmed', 4));

        $entry = $this->entries('order_status_forced')->sole();
        $this->assertSame(4, $entry->actor_id);
        $this->assertSame(['status' => 'shipped'], $entry->before);
        $this->assertSame(['status' => 'delivered'], $entry->after);
        $this->assertSame('Courier confirmed', $entry->reason);

        $paid = $this->orderWith($this->store, status: OrderStatus::Paid);
        try {
            $this->inStore(fn () => app(ForceOrderStatus::class)($paid, OrderStatus::Delivered, 'skip', 4));
            $this->fail('Skipping a step must be refused.');
        } catch (InvalidOrderTransition) {
            $this->assertCount(1, $this->entries('order_status_forced'));
        }
    }

    public function test_marking_a_payout_paid_and_rejecting_one_are_audited(): void
    {
        $this->deliveredDaysAgo($this->postedOrder($this->store, [[100000, 1, 10]]), 8);
        $this->artisan('ledger:release-funds')->assertSuccessful();
        $this->inStore(fn () => PayoutDetail::query()->create(['store_id' => $this->store->id, 'method' => PayoutMethod::Bank, 'destination' => 'IBAN']));

        $first = app(RequestPayout::class)($this->store, 50000, null);
        app(SettlePayout::class)->reject($first, 'Wrong IBAN', 7);
        $second = app(RequestPayout::class)($this->store, 60000, null);
        app(SettlePayout::class)->markPaid($second, 'TX-5', null, 7);

        $rejected = $this->entries('payout_rejected')->sole();
        $this->assertSame(7, $rejected->actor_id);
        $this->assertSame('Wrong IBAN', $rejected->reason);
        $paid = $this->entries('payout_paid')->sole();
        $this->assertSame(60000, $paid->after['amount']);
        $this->assertSame('TX-5', $paid->after['transfer_reference']);
    }

    public function test_the_admin_browses_and_filters_the_audit_log_and_vendors_cannot(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        Filament::setCurrentPanel('admin');
        Audit::record('payment_approved', $this->store);
        Audit::record('order_refunded', $this->store);

        $all = Livewire::test(ListAuditEntries::class)->removeTableFilters();
        $this->assertCount(2, $all->instance()->getTableRecords());

        $filtered = Livewire::test(ListAuditEntries::class)->removeTableFilters()->filterTable('action', 'order_refunded');
        $this->assertSame(['order_refunded'], $filtered->instance()->getTableRecords()->pluck('action')->all());

        $this->actingAs(User::factory()->vendorOwner($this->store)->create());
        $this->get('/admin/audit-entries')->assertForbidden();
    }

    private function pendingPayment(string $email): Payment
    {
        [$customer] = $this->customerWithAddress($this->store, $email);
        $order = $this->orderWith($this->store, [[100000, 1, 10]], 0, OrderStatus::PaymentReview, ['customer_id' => $customer->id]);
        $this->inStore(fn () => Variant::query()->whereKey($order->items()->firstOrFail()->variant_id)->increment('reserved'));

        return $this->inStore(fn (): Payment => Payment::factory()->create([
            'store_id' => $this->store->id,
            'order_id' => $order->id,
            'amount' => $order->total,
            'status' => PaymentStatus::PendingReview,
        ]));
    }
}
