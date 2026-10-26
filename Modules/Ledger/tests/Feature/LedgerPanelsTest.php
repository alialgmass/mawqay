<?php

namespace Modules\Ledger\Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Modules\Core\Support\StoreContext;
use Modules\Ledger\Enums\EntryDirection;
use Modules\Ledger\Enums\LedgerAccount;
use Modules\Ledger\Filament\Admin\Pages\VendorBalances;
use Modules\Ledger\Filament\Admin\Resources\LedgerEntries\LedgerEntryResource;
use Modules\Ledger\Filament\Admin\Resources\LedgerEntries\Pages\ListLedgerEntries;
use Modules\Ledger\Filament\Admin\Widgets\ReconciliationWidget;
use Modules\Ledger\Filament\Vendor\Pages\Statement;
use Modules\Ledger\Filament\Vendor\Widgets\BalanceStats;
use Modules\Ledger\Models\LedgerEntry;
use Modules\Ledger\Models\Reconciliation;
use Modules\Ledger\Services\BalanceService;
use Modules\Ledger\Services\ReversalService;
use Modules\Ledger\Testing\BuildsLedger;
use Modules\Payments\Testing\BuildsPayments;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Filament\Vendor\Resources\Orders\Pages\ViewOrder as VendorViewOrder;
use Modules\Sales\Models\Order;
use Modules\Sales\Support\CommissionCalculator;
use Modules\Tenancy\Filament\Admin\Resources\Stores\Pages\EditStore;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class LedgerPanelsTest extends TestCase
{
    use BuildsLedger;
    use BuildsPayments;
    use RefreshDatabase;

    private Store $storeA;

    private Store $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = $this->shop('store-a', ['name' => 'Alpha']);
        $this->storeB = $this->shop('store-b', ['name' => 'Beta']);
    }

    private function actAsAdmin(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel('admin');
    }

    private function actAsOwnerOfA(): void
    {
        $this->actingAs(User::factory()->vendorOwner($this->storeA)->create());
        Filament::setCurrentPanel('vendor');
        Filament::setTenant($this->storeA);
        app(StoreContext::class)->set($this->storeA->id);
    }

    // ----- Admin -----

    public function test_the_admin_sees_every_entry_and_cannot_create_edit_or_delete_any(): void
    {
        $this->actAsAdmin();
        $this->postedOrder($this->storeA);
        $this->postedOrder($this->storeB);

        Livewire::test(ListLedgerEntries::class)->assertCountTableRecords(6);

        $this->assertFalse(LedgerEntryResource::canCreate());
        $this->assertSame(['index'], array_keys(LedgerEntryResource::getPages()));
    }

    private function twoPostedOrders(): Order
    {
        $this->actAsAdmin();
        $one = $this->postedOrder($this->storeA);
        $this->postedOrder($this->storeB);

        return $one;
    }

    public function test_the_admin_filters_entries_by_store_including_that_stores_platform_rows(): void
    {
        $this->twoPostedOrders();

        Livewire::test(ListLedgerEntries::class)->filterTable('store_id', $this->storeA->id)->assertCountTableRecords(3);
    }

    public function test_the_admin_filters_entries_by_order(): void
    {
        $one = $this->twoPostedOrders();

        Livewire::test(ListLedgerEntries::class)->filterTable('order', ['number' => $one->number])->assertCountTableRecords(3);
    }

    public function test_the_admin_filters_entries_by_account(): void
    {
        $this->twoPostedOrders();

        Livewire::test(ListLedgerEntries::class)->filterTable('account', LedgerAccount::PlatformCash->value)->assertCountTableRecords(2);
    }

    public function test_the_admin_filters_entries_by_date(): void
    {
        $this->twoPostedOrders();

        Livewire::test(ListLedgerEntries::class)->filterTable('created', ['from' => now()->addDay()->toDateString()])->assertCountTableRecords(0);
    }

    public function test_the_admin_date_filter_includes_today(): void
    {
        $this->twoPostedOrders();

        Livewire::test(ListLedgerEntries::class)->filterTable('created', ['from' => now()->toDateString(), 'until' => now()->toDateString()])->assertCountTableRecords(6);
    }

    public function test_the_balances_page_lists_every_store_with_platform_totals(): void
    {
        $this->actAsAdmin();
        $this->postedOrder($this->storeA, [[100000, 1, 10]]);
        $this->postedOrder($this->storeB, [[50000, 1, 10]]);

        Livewire::test(VendorBalances::class)
            ->assertCanSeeTableRecords(Store::query()->get())
            ->assertSee('900 EGP')
            ->assertSee('450 EGP')
            ->assertSee('1,500 EGP');
    }

    public function test_only_platform_admins_open_the_balances_page(): void
    {
        $this->actingAs(User::factory()->vendorOwner($this->storeA)->create());

        $this->get('/admin/vendor-balances')->assertForbidden();
    }

    public function test_the_widget_shows_the_last_run_and_run_now_stores_a_new_one(): void
    {
        $this->actAsAdmin();
        $this->postedOrder($this->storeA);

        Livewire::test(ReconciliationWidget::class)
            ->assertSee(__('ledger::ledger.reconciliation.never'))
            ->callAction('runNow')
            ->assertNotified();

        $this->assertSame(1, Reconciliation::query()->count());
        $this->assertTrue(Reconciliation::query()->firstOrFail()->passed());
        Livewire::test(ReconciliationWidget::class)->assertSee(__('ledger::ledger.reconciliation.passed'));
    }

    public function test_the_widget_shows_a_mismatch(): void
    {
        $this->actAsAdmin();
        LedgerEntry::factory()->create(['store_id' => $this->storeA->id, 'amount' => 321]);
        Notification::fake();
        Artisan::call('ledger:reconcile');

        Livewire::test(ReconciliationWidget::class)
            ->assertSee(__('ledger::ledger.reconciliation.failed'))
            ->assertSee('Total debits differ from total credits.');
    }

    // ----- Commission override -----

    public function test_the_store_commission_override_accepts_zero_to_fifty_and_refuses_the_rest(): void
    {
        $this->actAsAdmin();

        foreach ([0, 25, 50] as $rate) {
            Livewire::test(EditStore::class, ['record' => $this->storeA->id])
                ->fillForm(['commission_rate' => $rate])
                ->call('save')
                ->assertHasNoFormErrors();
        }

        foreach ([50.01, 51, 100, -1] as $rate) {
            Livewire::test(EditStore::class, ['record' => $this->storeA->id])
                ->fillForm(['commission_rate' => $rate])
                ->call('save')
                ->assertHasFormErrors(['commission_rate']);
        }

        $this->assertSame('50.00', $this->storeA->fresh()->commission_rate);
    }

    public function test_a_changed_rate_applies_only_to_orders_placed_afterwards(): void
    {
        $before = $this->postedOrder($this->storeA, [[100000, 1, 10]]);

        $this->storeA->update(['commission_rate' => 20]);

        $this->assertSame('20.00', app(CommissionCalculator::class)->rateFor($this->storeA->fresh()));
        $this->assertSame(10000, (int) $before->items()->withoutGlobalScopes()->sum('commission_amount'));
        $this->assertSame(10000, (int) \DB::table('ledger_entries')->where('order_id', $before->id)->where('account', 'platform_commission_revenue')->value('amount'));
    }

    // ----- Vendor -----

    public function test_the_vendor_dashboard_widget_shows_pending_available_and_paid(): void
    {
        $this->actAsOwnerOfA();
        $this->postedOrder($this->storeA, [[100000, 1, 10]]);
        $this->deliveredDaysAgo($this->postedOrder($this->storeA, [[20000, 1, 10]]), 9);
        Artisan::call('ledger:release-funds');
        $this->postedOrder($this->storeB, [[999900, 1, 10]]);

        Livewire::test(BalanceStats::class)
            ->assertSee('900 EGP')
            ->assertSee('180 EGP')
            ->assertDontSee('8,999.10');
    }

    public function test_the_statement_lists_only_own_entries_with_a_running_balance(): void
    {
        $this->actAsOwnerOfA();
        $first = $this->postedOrder($this->storeA, [[100000, 1, 10]]);
        $second = $this->postedOrder($this->storeA, [[20000, 1, 10]]);
        $foreign = $this->postedOrder($this->storeB, [[77700, 1, 10]]);

        $component = Livewire::test(Statement::class);
        $records = LedgerEntry::query()->vendorAccounts()->where('store_id', $this->storeA->id)->get();

        $component->assertCanSeeTableRecords($records)->assertCountTableRecords(2)
            ->assertSee($first->number)->assertSee($second->number)->assertDontSee($foreign->number)
            ->assertSee('900 EGP')->assertSee('1,080 EGP');
    }

    public function test_the_statement_shows_the_platform_money_never(): void
    {
        $this->actAsOwnerOfA();
        $this->postedOrder($this->storeA, [[100000, 1, 10]]);

        $accounts = LedgerEntry::query()->withoutGlobalScopes()->whereNull('store_id')->pluck('id');

        Livewire::test(Statement::class)->assertCanNotSeeTableRecords($accounts);
    }

    public function test_the_statement_filters_by_date_range_without_resetting_the_running_balance(): void
    {
        $this->actAsOwnerOfA();
        $this->postedOrder($this->storeA, [[100000, 1, 10]]);
        $this->travel(2)->days();
        $this->postedOrder($this->storeA, [[20000, 1, 10]]);

        Livewire::test(Statement::class)
            ->filterTable('period', ['from' => now()->toDateString()])
            ->assertCountTableRecords(1)
            ->assertSee('1,080 EGP');

        Livewire::test(Statement::class)
            ->filterTable('period', ['until' => now()->subDay()->toDateString()])
            ->assertCountTableRecords(1)
            ->assertSee('900 EGP');
    }

    public function test_the_statement_shows_releases_and_refunds_in_arabic(): void
    {
        $this->actAsOwnerOfA();
        app()->setLocale('ar');
        $order = $this->deliveredDaysAgo($this->postedOrder($this->storeA, [[100000, 1, 10]]), 9);
        Artisan::call('ledger:release-funds');
        app(ReversalService::class)->reverse($order, 50000, 'refund:1');

        Livewire::test(Statement::class)
            ->assertCountTableRecords(4)
            ->assertSee('أصبح متاحًا للصرف')
            ->assertSee('استرجاع على الطلب');
    }

    public function test_the_vendor_order_page_shows_the_net_amount_and_nothing_about_payment(): void
    {
        $this->actAsOwnerOfA();
        $order = $this->postedOrder($this->storeA, [[120000, 1, 10]], shipping: 6000);

        Livewire::test(VendorViewOrder::class, ['record' => $order->getKey()])
            ->assertSee(__('ledger::ledger.vendor.net'))
            ->assertSee('1,140 EGP')
            ->assertDontSee('receipt')
            ->assertDontSee('transfer_reference');
    }

    // ----- Customer privacy and isolation -----

    public function test_no_customer_facing_page_carries_commission_or_ledger_figures(): void
    {
        $this->fakeReceiptDisk();
        $this->platformAccount();
        [$customer] = $this->customerWithAddress($this->storeA);
        $order = $this->orderFor($this->storeA, $customer, OrderStatus::Paid, null, 2, ['subtotal' => 20000, 'shipping_fee' => 5000, 'total' => 25000]);
        $this->approvedPaymentFor($order);
        $this->logInCustomer($this->storeA);

        foreach (["/store-a/orders/{$order->number}", "/store-a/orders/{$order->number}/confirmation", '/store-a/orders', '/store-a/cart'] as $url) {
            $response = $this->get($url)->assertOk();
            $props = json_encode($response->viewData('page')['props'] ?? []);

            foreach (['commission', 'ledger', 'vendor_payable', 'platform_cash', '"net"'] as $needle) {
                $this->assertStringNotContainsStringIgnoringCase($needle, $props, "{$url} leaked [{$needle}]");
            }
        }
    }

    public function test_ledger_rows_are_isolated_between_stores(): void
    {
        // The shared helper also tries bulk updates on foreign rows, which entries refuse outright, so the read side is checked here.
        $rowA = LedgerEntry::factory()->create(['store_id' => $this->storeA->id]);
        $rowB = LedgerEntry::factory()->create(['store_id' => $this->storeB->id]);

        app(StoreContext::class)->runAs($this->storeA->id, function () use ($rowA, $rowB): void {
            $this->assertSame([$rowA->id], LedgerEntry::query()->get()->modelKeys());
            $this->assertSame(1, LedgerEntry::query()->count());
            $this->assertNull(LedgerEntry::query()->find($rowB->id));
        });
    }

    public function test_platform_rows_are_invisible_inside_any_store(): void
    {
        $this->postedOrder($this->storeA);

        app(StoreContext::class)->runAs($this->storeA->id, function (): void {
            $this->assertSame(1, LedgerEntry::query()->count());
            $this->assertSame(['vendor_payable_pending'], LedgerEntry::query()->get()->map(fn (LedgerEntry $entry): string => $entry->account->value)->all());
        });

        $this->assertSame(0, LedgerEntry::query()->count(), 'no Store context sees nothing');
    }

    public function test_balances_only_count_the_stores_own_credit_and_debit_directions(): void
    {
        LedgerEntry::factory()->create(['store_id' => $this->storeA->id, 'amount' => 500, 'direction' => EntryDirection::Credit]);
        LedgerEntry::factory()->create(['store_id' => $this->storeA->id, 'amount' => 200, 'direction' => EntryDirection::Debit]);

        $this->assertSame(300, app(BalanceService::class)->forStore($this->storeA->id)->pending);
        $this->assertSame(0, app(BalanceService::class)->forStore($this->storeB->id)->pending);
    }
}
