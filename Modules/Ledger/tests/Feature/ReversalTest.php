<?php

namespace Modules\Ledger\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Ledger\Exceptions\ReversalNotPossible;
use Modules\Ledger\Models\LedgerEntry;
use Modules\Ledger\Services\BalanceService;
use Modules\Ledger\Services\ReversalService;
use Modules\Ledger\Testing\BuildsLedger;
use Modules\Sales\Models\Order;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class ReversalTest extends TestCase
{
    use BuildsLedger;
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = $this->shop('store-a');
    }

    private function held(Order $order): array
    {
        return app(BalanceService::class)->forOrder($order->id);
    }

    private function assertBalanced(): void
    {
        $difference = DB::table('ledger_entries')
            ->selectRaw("coalesce(sum(case when direction = 'debit' then amount else -amount end), 0) as difference")
            ->value('difference');

        $this->assertSame(0, (int) $difference);
    }

    public function test_a_full_reversal_empties_the_order_and_points_at_the_originals(): void
    {
        $order = $this->postedOrder($this->store, [[100000, 1, 10]], shipping: 5000);
        $originals = DB::table('ledger_entries')->where('order_id', $order->id)->pluck('id', 'account');

        $entries = app(ReversalService::class)->reverse($order, null, 'refund:1');

        $held = $this->held($order);
        $this->assertSame(0, $held['platform_cash']);
        $this->assertSame(0, $held['platform_commission_revenue']);
        $this->assertSame(0, $held['vendor_payable_pending'] ?? 0);
        $this->assertCount(3, $entries);

        foreach ($entries as $entry) {
            $this->assertSame($originals[$entry->account->value], $entry->reverses_entry_id);
        }

        $this->assertBalanced();
    }

    public function test_a_partial_reversal_takes_commission_in_proportion(): void
    {
        // Cash 126,000: Vendor 114,000 and Commission 12,000. Refund a quarter of it.
        $order = $this->postedOrder($this->store, [[120000, 1, 10]], shipping: 6000);

        app(ReversalService::class)->reverse($order, 31500, 'refund:1');

        $held = $this->held($order);
        $this->assertSame(94500, $held['platform_cash']);
        $this->assertSame(9000, $held['platform_commission_revenue']);
        $this->assertSame(85500, $held['vendor_payable_pending']);
        $this->assertBalanced();
    }

    public function test_rounding_keeps_a_partial_reversal_in_balance(): void
    {
        $order = $this->postedOrder($this->store, [[3333, 3, 7.5]], shipping: 101);

        app(ReversalService::class)->reverse($order, 3333, 'refund:1');
        app(ReversalService::class)->reverse($order, 1, 'refund:2');

        $held = $this->held($order);
        $this->assertSame($held['platform_cash'], $held['platform_commission_revenue'] + $held['vendor_payable_pending']);
        $this->assertBalanced();
    }

    public function test_successive_partial_reversals_can_empty_the_order_exactly(): void
    {
        $order = $this->postedOrder($this->store, [[9999, 1, 12.5]], shipping: 333);

        app(ReversalService::class)->reverse($order, 3000, 'refund:1');
        app(ReversalService::class)->reverse($order, 3000, 'refund:2');
        app(ReversalService::class)->reverse($order, null, 'refund:3');

        $held = $this->held($order);
        $this->assertSame(0, $held['platform_cash']);
        $this->assertSame(0, $held['platform_commission_revenue']);
        $this->assertSame(0, $held['vendor_payable_pending']);
        $this->assertBalanced();
    }

    public function test_the_same_reference_reverses_once(): void
    {
        $order = $this->postedOrder($this->store, [[100000, 1, 10]]);

        app(ReversalService::class)->reverse($order, 50000, 'refund:1');
        $again = app(ReversalService::class)->reverse($order, 50000, 'refund:1');

        $this->assertSame([], $again);
        $this->assertSame(50000, $this->held($order)['platform_cash']);
    }

    public function test_more_than_is_held_is_refused(): void
    {
        $order = $this->postedOrder($this->store, [[100000, 1, 10]]);

        $this->expectException(ReversalNotPossible::class);

        app(ReversalService::class)->reverse($order, 100001, 'refund:1');
    }

    public function test_an_order_with_nothing_posted_cannot_be_reversed(): void
    {
        $order = $this->orderWith($this->store, [[100000, 1, 10]]);

        $this->expectException(ReversalNotPossible::class);

        app(ReversalService::class)->reverse($order, null, 'refund:1');
    }

    public function test_after_release_a_reversal_draws_on_available(): void
    {
        $order = $this->deliveredDaysAgo($this->postedOrder($this->store, [[100000, 1, 10]]), 8);
        Artisan::call('ledger:release-funds');
        $availableCredit = DB::table('ledger_entries')->where('order_id', $order->id)->where('account', 'vendor_payable_available')->value('id');

        $entries = app(ReversalService::class)->reverse($order, 40000, 'refund:1');

        $held = $this->held($order);
        $this->assertSame(54000, $held['vendor_payable_available']);
        $this->assertSame(0, $held['vendor_payable_pending']);
        $reversal = collect($entries)->first(fn (LedgerEntry $entry): bool => $entry->account->value === 'vendor_payable_available');
        $this->assertSame((int) $availableCredit, $reversal->reverses_entry_id);
        $this->assertBalanced();
    }

    public function test_before_release_a_reversal_draws_on_pending(): void
    {
        $order = $this->postedOrder($this->store, [[100000, 1, 10]]);

        app(ReversalService::class)->reverse($order, 40000, 'refund:1');

        $held = $this->held($order);
        $this->assertSame(54000, $held['vendor_payable_pending']);
        $this->assertSame(0, $held['vendor_payable_available'] ?? 0);
    }
}
