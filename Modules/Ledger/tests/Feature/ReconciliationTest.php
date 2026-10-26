<?php

namespace Modules\Ledger\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Modules\Ledger\Enums\EntryDirection;
use Modules\Ledger\Enums\LedgerAccount;
use Modules\Ledger\Enums\ReconciliationStatus;
use Modules\Ledger\Models\LedgerEntry;
use Modules\Ledger\Models\Reconciliation;
use Modules\Ledger\Notifications\ReconciliationFailed;
use Modules\Ledger\Services\ReconcileLedger;
use Modules\Ledger\Services\ReversalService;
use Modules\Ledger\Testing\BuildsLedger;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Models\Order;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class ReconciliationTest extends TestCase
{
    use BuildsLedger;
    use RefreshDatabase;

    private Store $store;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = $this->shop('store-a');
        $this->admin = User::factory()->admin()->create();
    }

    public function test_valid_books_pass_and_the_result_is_stored(): void
    {
        $this->postedOrder($this->store, [[100000, 2, 10]], shipping: 5000);
        $this->orderWith($this->store, [[5000, 1, 10]], status: OrderStatus::PendingPayment);
        Notification::fake();

        $run = app(ReconcileLedger::class)($this->admin->id);

        $this->assertSame(ReconciliationStatus::Passed, $run->status);
        $this->assertNull($run->mismatches);
        $this->assertSame($run->total_debits, $run->total_credits);
        $this->assertSame(205000, $run->platform_cash);
        $this->assertSame($this->admin->id, $run->ran_by);
        $this->assertSame(1, Reconciliation::query()->count());
        Notification::assertNothingSent();
    }

    public function test_the_command_succeeds_on_valid_books(): void
    {
        $this->postedOrder($this->store);

        $this->artisan('ledger:reconcile')->expectsOutputToContain('balance')->assertSuccessful();
    }

    public function test_an_unbalanced_entry_is_reported_and_every_admin_is_told(): void
    {
        $this->postedOrder($this->store);
        $second = User::factory()->admin()->create();
        $vendor = User::factory()->vendorOwner($this->store)->create();
        LedgerEntry::query()->create([
            'store_id' => $this->store->id, 'posting_group' => 'planted:1', 'account' => LedgerAccount::VendorPayablePending,
            'direction' => EntryDirection::Credit, 'amount' => 777, 'idempotency_key' => 'planted:1:a',
        ]);
        Notification::fake();

        $this->assertSame(1, Artisan::call('ledger:reconcile'));

        $run = Reconciliation::query()->latest('id')->firstOrFail();
        $this->assertSame(ReconciliationStatus::Failed, $run->status);
        $types = collect($run->mismatches)->pluck('type')->all();
        $this->assertContains('totals', $types);
        $this->assertContains('group', $types);
        $this->assertContains('cash', $types);
        Notification::assertSentTo([$this->admin, $second], ReconciliationFailed::class);
        Notification::assertNotSentTo($vendor, ReconciliationFailed::class);
    }

    public function test_a_paid_order_with_no_entries_is_reported(): void
    {
        $order = $this->orderWith($this->store, [[100000, 1, 10]]);
        Notification::fake();

        $run = app(ReconcileLedger::class)();

        $this->assertSame(ReconciliationStatus::Failed, $run->status);
        $this->assertTrue(collect($run->mismatches)->contains(fn (array $mismatch): bool => ($mismatch['order_number'] ?? null) === $order->number));
        Notification::assertSentTo($this->admin, ReconciliationFailed::class);
    }

    public function test_money_recorded_for_an_unpaid_order_is_reported(): void
    {
        $order = $this->orderWith($this->store, [[100000, 1, 10]], status: OrderStatus::PendingPayment);
        $this->approvedPaymentFor($order);
        Notification::fake();

        $run = app(ReconcileLedger::class)();

        $this->assertSame(ReconciliationStatus::Failed, $run->status);
    }

    public function test_an_order_completed_without_releasing_its_money_is_reported(): void
    {
        $order = $this->postedOrder($this->store);
        Order::query()->withoutGlobalScopes()->whereKey($order->id)->update(['status' => OrderStatus::Completed->value]);
        Notification::fake();

        $this->assertSame(ReconciliationStatus::Failed, app(ReconcileLedger::class)()->status);
    }

    public function test_reversed_and_released_orders_still_reconcile(): void
    {
        $refunded = $this->postedOrder($this->store, [[100000, 1, 10]]);
        app(ReversalService::class)->reverse($refunded, null, 'refund:1');
        Order::query()->withoutGlobalScopes()->whereKey($refunded->id)->update(['status' => OrderStatus::Refunded->value]);
        $this->deliveredDaysAgo($this->postedOrder($this->store, [[50000, 1, 10]]), 9);
        Artisan::call('ledger:release-funds');

        $this->assertSame(ReconciliationStatus::Passed, app(ReconcileLedger::class)()->status);
    }

    public function test_random_orders_keep_the_books_reconciled(): void
    {
        $other = $this->shop('store-b');

        // Fixed seed so a failure can be replayed; reseeded afterwards so Faker still finds unique values.
        mt_srand(20261026);
        $cases = [];

        for ($i = 0; $i < 40; $i++) {
            $lines = [];

            for ($line = 0, $count = mt_rand(1, 4); $line < $count; $line++) {
                $lines[] = [mt_rand(1, 250000), mt_rand(1, 5), mt_rand(0, 5000) / 100];
            }

            $shipping = mt_rand(0, 20000);
            $cases[] = [$lines, $shipping, mt_rand(0, 1000000) / 1000000];
        }

        mt_srand();

        foreach ($cases as $i => [$lines, $shipping, $fraction]) {
            $order = $this->postedOrder($i % 2 === 0 ? $this->store : $other, $lines, $shipping);

            if ($i % 3 === 0) {
                app(ReversalService::class)->reverse($order, max(1, (int) floor($order->total * $fraction)), "refund:{$i}");
            }

            if ($i % 5 === 0) {
                $this->deliveredDaysAgo($order, 10);
            }
        }

        Artisan::call('ledger:release-funds');
        Notification::fake();

        $run = app(ReconcileLedger::class)();

        $this->assertSame([], $run->mismatches ?? [], json_encode($run->mismatches));
        $this->assertSame(ReconciliationStatus::Passed, $run->status);
    }

    public function test_each_run_is_stored(): void
    {
        $this->postedOrder($this->store);

        app(ReconcileLedger::class)();
        app(ReconcileLedger::class)();

        $this->assertSame(2, DB::table('ledger_reconciliations')->count());
    }
}
