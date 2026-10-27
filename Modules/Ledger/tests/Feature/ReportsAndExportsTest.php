<?php

namespace Modules\Ledger\Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Modules\Core\Support\StoreContext;
use Modules\Ledger\Actions\RequestPayout;
use Modules\Ledger\Enums\PayoutMethod;
use Modules\Ledger\Filament\Admin\Pages\Reports;
use Modules\Ledger\Filament\Admin\Widgets\PlatformStats;
use Modules\Ledger\Filament\Vendor\Pages\Statement;
use Modules\Ledger\Filament\Vendor\Widgets\BalanceStats;
use Modules\Ledger\Filament\Vendor\Widgets\SalesChart;
use Modules\Ledger\Filament\Vendor\Widgets\TopProductsChart;
use Modules\Ledger\Models\PayoutDetail;
use Modules\Ledger\Services\BalanceService;
use Modules\Ledger\Services\ReportService;
use Modules\Ledger\Support\Csv;
use Modules\Ledger\Testing\BuildsLedger;
use Modules\Payments\Enums\PaymentStatus;
use Modules\Payments\Models\Payment;
use Modules\Sales\Actions\RefundOrder;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Enums\RefundReason;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class ReportsAndExportsTest extends TestCase
{
    use BuildsLedger;
    use RefreshDatabase;

    private Store $storeA;

    private Store $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = $this->shop('store-a', ['name' => 'Alpha']);
        $this->storeB = $this->shop('store-b', ['name' => 'Beta']);
    }

    /**
     * Alpha sold 1,000 EGP (10%) and 1,000 EGP (20%), refunded 300 EGP of the first, and had one cancelled Order.
     * Beta sold 2,000 EGP (10%).
     */
    private function seedSales(): void
    {
        $first = $this->postedOrder($this->storeA, [[100000, 1, 10]]);
        $this->postedOrder($this->storeA, [[50000, 2, 20]]);
        $this->orderWith($this->storeA, [[70000, 1, 10]], 0, OrderStatus::Cancelled);
        $this->postedOrder($this->storeB, [[200000, 1, 10]]);

        app(StoreContext::class)->runAs($this->storeA->id, fn () => app(RefundOrder::class)($first, 30000, RefundReason::Other, null, null, 'seed-refund'));
    }

    private function range(): array
    {
        return [now()->subDays(29)->startOfDay(), now()->endOfDay()];
    }

    private function actAsOwnerOfA(): void
    {
        $this->actingAs(User::factory()->vendorOwner($this->storeA)->create());
        Filament::setCurrentPanel('vendor');
        Filament::setTenant($this->storeA);
        app(StoreContext::class)->set($this->storeA->id);
    }

    private function actAsAdmin(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel('admin');
    }

    private function csv(object $test): string
    {
        $download = $test->effects['download'] ?? null;
        $this->assertNotNull($download, 'Expected a file download.');

        return base64_decode($download['content']);
    }

    /**
     * @return list<list<string>>
     */
    private function rows(string $csv): array
    {
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);

        return array_map(fn (string $line): array => str_getcsv($line, escape: '\\'), array_values(array_filter(explode("\n", substr($csv, 3)))));
    }

    // ----- Report figures -----

    public function test_gmv_is_sales_net_of_refunds_and_ignores_cancelled_orders(): void
    {
        $this->seedSales();
        [$from, $until] = $this->range();
        $reports = app(ReportService::class);

        $this->assertSame(170000, $reports->gmv($this->storeA->id, $from, $until));
        $this->assertSame(200000, $reports->gmv($this->storeB->id, $from, $until));
        $this->assertSame(370000, $reports->gmv(null, $from, $until));
    }

    public function test_commission_revenue_comes_from_the_books_net_of_reversals(): void
    {
        $this->seedSales();
        [$from, $until] = $this->range();
        $reports = app(ReportService::class);

        $this->assertSame(27000, $reports->commissionRevenue($this->storeA->id, $from, $until));
        $this->assertSame(47000, $reports->commissionRevenue(null, $from, $until));
        $this->assertSame(47000, app(BalanceService::class)->platformTotals()['commission_revenue']);
    }

    public function test_sales_by_day_covers_every_day_and_nets_refunds(): void
    {
        $this->seedSales();
        [$from, $until] = $this->range();

        $days = app(ReportService::class)->salesByDay($this->storeA->id, $from, $until);

        $this->assertCount(30, $days);
        $this->assertSame(['orders' => 2, 'gmv' => 170000], $days[now()->toDateString()]);
        $this->assertSame(['orders' => 0, 'gmv' => 0], $days[now()->subDays(5)->toDateString()]);
    }

    public function test_orders_by_status_and_top_products_exclude_what_was_not_sold(): void
    {
        $this->seedSales();
        [$from, $until] = $this->range();
        $reports = app(ReportService::class);

        $this->assertEquals(['paid' => 2, 'cancelled' => 1], $reports->ordersByStatus($this->storeA->id));
        $this->assertEquals(['paid' => 3, 'cancelled' => 1], $reports->ordersByStatus(null));
        $this->assertSame([['name' => 'Abaya', 'quantity' => 3, 'revenue' => 200000]], $reports->topProducts($this->storeA->id, $from, $until));
    }

    public function test_revenue_by_store_lists_the_biggest_first(): void
    {
        $this->seedSales();
        [$from, $until] = $this->range();

        $this->assertSame([
            ['store_id' => $this->storeB->id, 'store' => 'Beta', 'orders' => 1, 'gmv' => 200000, 'commission' => 20000],
            ['store_id' => $this->storeA->id, 'store' => 'Alpha', 'orders' => 2, 'gmv' => 170000, 'commission' => 27000],
        ], app(ReportService::class)->revenueByStore($from, $until));
    }

    public function test_orders_outside_the_period_are_left_out(): void
    {
        $order = $this->postedOrder($this->storeA, [[100000, 1, 10]]);
        app(StoreContext::class)->runAs($this->storeA->id, fn () => $order->forceFill(['created_at' => now()->subDays(40)])->saveQuietly());

        [$from, $until] = $this->range();

        $this->assertSame(0, app(ReportService::class)->gmv($this->storeA->id, $from, $until));
    }

    public function test_figures_are_cached_briefly_and_never_shared_between_stores(): void
    {
        $this->seedSales();
        [$from, $until] = $this->range();
        $reports = app(ReportService::class);

        $this->assertSame(170000, $reports->gmv($this->storeA->id, $from, $until));
        $this->postedOrder($this->storeA, [[100000, 1, 10]]);

        $this->assertSame(170000, $reports->gmv($this->storeA->id, $from, $until), 'Served from the short cache.');
        $this->assertSame(200000, $reports->gmv($this->storeB->id, $from, $until), 'Another Store never reads this Store cache.');

        Cache::flush();

        $this->assertSame(270000, $reports->gmv($this->storeA->id, $from, $until));
    }

    public function test_receipts_and_payouts_awaiting_are_counted(): void
    {
        $order = $this->orderWith($this->storeA, status: OrderStatus::PaymentReview);
        app(StoreContext::class)->runAs($this->storeA->id, fn () => Payment::factory()->create(['store_id' => $this->storeA->id, 'order_id' => $order->id, 'amount' => $order->total, 'status' => PaymentStatus::PendingReview]));
        $this->deliveredDaysAgo($this->postedOrder($this->storeA, [[100000, 1, 10]]), 8);
        $this->artisan('ledger:release-funds')->assertSuccessful();
        app(StoreContext::class)->runAs($this->storeA->id, fn () => PayoutDetail::query()->create(['store_id' => $this->storeA->id, 'method' => PayoutMethod::Bank, 'destination' => 'IBAN']));
        app(RequestPayout::class)($this->storeA, 60000, null);

        $reports = app(ReportService::class);

        $this->assertSame(1, $reports->receiptsAwaiting());
        $this->assertSame(['count' => 1, 'amount' => 60000], $reports->payoutsAwaiting());
    }

    // ----- Dashboards -----

    public function test_the_admin_dashboard_widgets_show_the_platform_figures(): void
    {
        $this->seedSales();
        $this->actAsAdmin();

        Livewire::test(PlatformStats::class)->assertSee('3,700 EGP')->assertSee('470 EGP');
    }

    public function test_the_vendor_dashboard_shows_orders_to_ship_and_renders_the_charts(): void
    {
        $this->seedSales();
        $this->orderWith($this->storeA, status: OrderStatus::Processing);
        $this->actAsOwnerOfA();

        Livewire::test(BalanceStats::class)->assertSee(__('ledger::reports.to_ship'));
        Livewire::test(SalesChart::class)->assertOk();
        Livewire::test(TopProductsChart::class)->assertOk()->assertSee('Abaya', false);
    }

    // ----- Exports -----

    public function test_the_statement_export_carries_date_order_description_amount_and_running_balance(): void
    {
        $order = $this->deliveredDaysAgo($this->postedOrder($this->storeA, [[100000, 1, 10]]), 8);
        $this->artisan('ledger:release-funds')->assertSuccessful();
        $this->actAsOwnerOfA();

        $test = Livewire::test(Statement::class)->callAction('export', ['from' => null, 'until' => null]);
        $rows = $this->rows($this->csv($test));

        $this->assertSame([__('ledger::ledger.statement.date'), __('ledger::ledger.statement.order'), __('ledger::ledger.statement.description'), __('ledger::ledger.statement.amount'), __('ledger::ledger.statement.running_balance')], $rows[0]);
        $this->assertCount(4, $rows);
        $this->assertSame($order->number, $rows[1][1]);
        $this->assertSame('900.00', $rows[1][3]);
        $this->assertSame('900.00', $rows[1][4]);
        $this->assertSame('-900.00', $rows[2][3]);
        $this->assertSame('0.00', $rows[2][4]);
        $this->assertSame('900.00', $rows[3][3]);
        $this->assertSame('900.00', $rows[3][4]);
    }

    public function test_the_statement_export_filters_by_date_range(): void
    {
        $this->postedOrder($this->storeA, [[100000, 1, 10]]);
        $this->actAsOwnerOfA();

        $future = Livewire::test(Statement::class)->callAction('export', ['from' => now()->addDay()->toDateString(), 'until' => null]);
        $today = Livewire::test(Statement::class)->callAction('export', ['from' => now()->toDateString(), 'until' => now()->toDateString()]);

        $this->assertCount(1, $this->rows($this->csv($future)));
        $this->assertCount(2, $this->rows($this->csv($today)));
    }

    public function test_the_statement_export_holds_only_the_vendors_own_store(): void
    {
        $mine = $this->postedOrder($this->storeA, [[100000, 1, 10]]);
        $theirs = $this->postedOrder($this->storeB, [[200000, 1, 10]]);
        $this->actAsOwnerOfA();

        $csv = $this->csv(Livewire::test(Statement::class)->callAction('export', []));

        $this->assertStringContainsString($mine->number, $csv);
        $this->assertStringNotContainsString($theirs->number, $csv);
    }

    public function test_the_revenue_export_lists_each_store_with_exact_amounts(): void
    {
        $this->seedSales();
        $this->actAsAdmin();

        $test = Livewire::test(Reports::class)->set('from', now()->subDays(29)->toDateString())->set('until', now()->toDateString())->callAction('export');
        $rows = $this->rows($this->csv($test));

        $this->assertSame([__('ledger::reports.store'), __('ledger::reports.orders'), __('ledger::reports.gmv'), __('ledger::reports.commission')], $rows[0]);
        $this->assertSame(['Beta', '1', '2000.00', '200.00'], $rows[1]);
        $this->assertSame(['Alpha', '2', '1700.00', '270.00'], $rows[2]);
    }

    public function test_text_that_looks_like_a_formula_is_defused_and_numbers_are_left_alone(): void
    {
        $this->assertSame("'=HYPERLINK(\"x\")", Csv::cell('=HYPERLINK("x")'));
        $this->assertSame("'+1+1", Csv::cell('+1+1'));
        $this->assertSame("'@SUM(A1)", Csv::cell('@SUM(A1)'));
        $this->assertSame('-12.50', Csv::cell('-12.50'));
        $this->assertSame('متجر النور', Csv::cell('متجر النور'));
        $this->assertSame('1234.50', Csv::money(123450));
        $this->assertSame('-0.05', Csv::money(-5));
    }

    public function test_a_store_name_cannot_inject_a_formula_into_the_revenue_export(): void
    {
        $this->storeA->update(['name' => '=cmd|calc']);
        $this->postedOrder($this->storeA, [[100000, 1, 10]]);
        $this->actAsAdmin();

        $csv = $this->csv(Livewire::test(Reports::class)->callAction('export'));

        $this->assertStringContainsString("'=cmd|calc", $csv);
    }

    public function test_arabic_text_survives_the_export(): void
    {
        $this->storeA->update(['name' => 'متجر النور']);
        $this->postedOrder($this->storeA, [[100000, 1, 10]]);
        $this->actAsAdmin();

        $csv = $this->csv(Livewire::test(Reports::class)->callAction('export'));

        $this->assertStringContainsString('متجر النور', $csv);
    }

    public function test_vendors_cannot_reach_the_admin_reports(): void
    {
        $this->actingAs(User::factory()->vendorOwner($this->storeA)->create());

        $this->get('/admin/reports')->assertForbidden();
    }
}
