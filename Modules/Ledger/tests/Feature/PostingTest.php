<?php

namespace Modules\Ledger\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Core\Support\StoreContext;
use Modules\Ledger\Actions\PostPayment;
use Modules\Ledger\Enums\EntryDirection;
use Modules\Ledger\Enums\LedgerAccount;
use Modules\Ledger\Exceptions\LedgerEntryIsImmutable;
use Modules\Ledger\Exceptions\UnbalancedPosting;
use Modules\Ledger\Models\LedgerEntry;
use Modules\Ledger\Services\BalanceService;
use Modules\Ledger\Services\Poster;
use Modules\Ledger\Testing\BuildsLedger;
use Modules\Payments\Actions\ApprovePayment;
use Modules\Payments\Enums\PaymentStatus;
use Modules\Payments\Models\Payment;
use Modules\Payments\Testing\BuildsPayments;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Models\Order;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class PostingTest extends TestCase
{
    use BuildsLedger;
    use BuildsPayments;
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = $this->shop('store-a');
    }

    /**
     * @return array<string, array{debit: int, credit: int}>
     */
    private function totalsByAccount(Order $order): array
    {
        $rows = DB::table('ledger_entries')->where('order_id', $order->id)->get();
        $totals = [];

        foreach ($rows as $row) {
            $totals[$row->account] ??= ['debit' => 0, 'credit' => 0];
            $totals[$row->account][$row->direction] += $row->amount;
        }

        return $totals;
    }

    public function test_the_worked_example_posts_cash_vendor_money_and_commission_to_the_piaster(): void
    {
        // 1,200 EGP of products at 10% plus 60 EGP shipping.
        $order = $this->postedOrder($this->store, [[120000, 1, 10]], shipping: 6000);

        $totals = $this->totalsByAccount($order);

        $this->assertSame(['debit' => 126000, 'credit' => 0], $totals['platform_cash']);
        $this->assertSame(['debit' => 0, 'credit' => 114000], $totals['vendor_payable_pending']);
        $this->assertSame(['debit' => 0, 'credit' => 12000], $totals['platform_commission_revenue']);
    }

    public function test_approving_a_payment_through_payments_posts_the_ledger(): void
    {
        $this->fakeReceiptDisk();
        $this->platformAccount();
        [$customer] = $this->customerWithAddress($this->store);
        $order = $this->orderFor($this->store, $customer, OrderStatus::PaymentReview, null, 2, ['subtotal' => 20000, 'shipping_fee' => 5000, 'total' => 25000]);
        $payment = $this->paymentFor($order);
        $admin = User::factory()->admin()->create();

        app(ApprovePayment::class)($payment, $admin->id);

        $totals = $this->totalsByAccount($order);
        $this->assertSame(25000, $totals['platform_cash']['debit']);
        $this->assertSame(2000, $totals['platform_commission_revenue']['credit']);
        $this->assertSame(23000, $totals['vendor_payable_pending']['credit']);
        $this->assertSame(PaymentStatus::Approved, $payment->fresh()->status);
    }

    public function test_commission_is_rounded_per_item_and_the_vendor_takes_the_remainder(): void
    {
        // 3 x 3.33 EGP at 10% -> 33 piasters of 333; 12.35 EGP at 12.5% -> 154.375 -> 154; 99 piasters at 33.33% -> 33.
        $order = $this->postedOrder($this->store, [[333, 3, 10], [1235, 1, 12.5], [99, 1, 33.33]], shipping: 1);

        $commission = 100 + 154 + 33;
        $totals = $this->totalsByAccount($order);

        $this->assertSame($commission, $totals['platform_commission_revenue']['credit']);
        $this->assertSame((int) $order->total - $commission, $totals['vendor_payable_pending']['credit']);
        $this->assertSame((int) $order->total, $totals['platform_cash']['debit']);
    }

    public function test_every_posting_group_balances(): void
    {
        $this->postedOrder($this->store, [[12345, 3, 7.5], [999, 2, 15]], shipping: 4321);
        $this->postedOrder($this->store, [[1, 1, 50]], shipping: 0);

        $unbalanced = DB::table('ledger_entries')
            ->selectRaw("posting_group, sum(case when direction = 'debit' then amount else -amount end) as difference")
            ->groupBy('posting_group')
            ->get()
            ->filter(fn ($row): bool => (int) $row->difference !== 0);

        $this->assertCount(0, $unbalanced);
    }

    public function test_shipping_fees_carry_no_commission(): void
    {
        $order = $this->postedOrder($this->store, [[10000, 1, 10]], shipping: 5000);

        $totals = $this->totalsByAccount($order);

        $this->assertSame(1000, $totals['platform_commission_revenue']['credit']);
        $this->assertSame(14000, $totals['vendor_payable_pending']['credit']);
    }

    public function test_the_commission_comes_from_the_rate_frozen_on_the_order_items(): void
    {
        $order = $this->orderWith($this->store, [[100000, 1, 10]]);
        $this->store->update(['commission_rate' => 40]);

        $this->approvedPaymentFor($order);

        $this->assertSame(10000, $this->totalsByAccount($order)['platform_commission_revenue']['credit']);
    }

    public function test_the_same_payment_approved_event_twice_posts_once(): void
    {
        $order = $this->orderWith($this->store, [[100000, 1, 10]]);
        $payment = $this->approvedPaymentFor($order);

        $second = app(PostPayment::class)($payment, $order);

        $this->assertSame([], $second);
        $this->assertSame(3, LedgerEntry::query()->withoutGlobalScopes()->count());
    }

    public function test_a_racing_duplicate_that_slips_past_the_check_still_posts_once(): void
    {
        $order = $this->orderWith($this->store, [[100000, 1, 10]]);
        $debit = ['key' => 'a', 'account' => LedgerAccount::PlatformCash, 'direction' => EntryDirection::Debit, 'amount' => 5, 'order_id' => $order->id];
        $credit = ['key' => 'b', 'account' => LedgerAccount::PlatformCommissionRevenue, 'direction' => EntryDirection::Credit, 'amount' => 5, 'order_id' => $order->id];

        // Another process wrote the second line after our existence check on the first.
        LedgerEntry::query()->create([...$credit, 'posting_group' => 'race:1', 'idempotency_key' => 'race:1:b']);

        $this->assertSame([], app(Poster::class)->post('race:1', [$debit, $credit]));
        $this->assertSame(1, DB::table('ledger_entries')->where('posting_group', 'race:1')->count());
    }

    public function test_an_unbalanced_group_is_refused_and_nothing_is_written(): void
    {
        $this->expectException(UnbalancedPosting::class);

        try {
            app(Poster::class)->post('bad:1', [
                ['key' => 'a', 'account' => LedgerAccount::PlatformCash, 'direction' => EntryDirection::Debit, 'amount' => 100],
                ['key' => 'b', 'account' => LedgerAccount::PlatformCommissionRevenue, 'direction' => EntryDirection::Credit, 'amount' => 99],
            ]);
        } finally {
            $this->assertSame(0, DB::table('ledger_entries')->count());
        }
    }

    public function test_an_order_whose_total_disagrees_with_its_lines_cannot_be_posted(): void
    {
        $order = $this->orderWith($this->store, [[100000, 1, 10]], attributes: ['total' => 123]);
        $payment = $this->unannouncedPaymentFor($order);

        $this->expectException(UnbalancedPosting::class);

        app(PostPayment::class)($payment, $order);
    }

    private function unannouncedPaymentFor(Order $order): Payment
    {
        return app(StoreContext::class)->runAs($order->store_id, fn () => Payment::factory()->create([
            'store_id' => $order->store_id, 'order_id' => $order->id, 'status' => PaymentStatus::Approved,
        ]));
    }

    public function test_entries_cannot_be_updated_or_deleted_through_the_model(): void
    {
        $order = $this->postedOrder($this->store);
        $entry = LedgerEntry::query()->withoutGlobalScopes()->where('order_id', $order->id)->firstOrFail();

        try {
            $entry->update(['amount' => 1]);
            $this->fail('update was allowed');
        } catch (LedgerEntryIsImmutable) {
        }

        try {
            $entry->delete();
            $this->fail('delete was allowed');
        } catch (LedgerEntryIsImmutable) {
        }

        $this->assertSame(100000, $entry->fresh()->amount);
    }

    public function test_entries_cannot_be_changed_or_removed_in_bulk(): void
    {
        $this->postedOrder($this->store);

        foreach ([
            fn () => LedgerEntry::query()->withoutGlobalScopes()->update(['amount' => 1]),
            fn () => LedgerEntry::query()->withoutGlobalScopes()->delete(),
            fn () => LedgerEntry::query()->withoutGlobalScopes()->increment('amount'),
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('a bulk change was allowed');
            } catch (LedgerEntryIsImmutable) {
            }
        }

        $this->assertSame(3, LedgerEntry::query()->withoutGlobalScopes()->count());
    }

    public function test_balances_follow_the_entries_and_refresh_after_each_posting(): void
    {
        $balances = app(BalanceService::class);
        $this->assertSame(0, $balances->forStore($this->store->id)->pending);

        $this->postedOrder($this->store, [[100000, 1, 10]], shipping: 5000);
        $this->assertSame(95000, $balances->forStore($this->store->id)->pending);

        $this->postedOrder($this->store, [[20000, 1, 10]]);
        $balance = $balances->forStore($this->store->id);

        $this->assertSame(95000 + 18000, $balance->pending);
        $this->assertSame(0, $balance->available);
        $this->assertSame(0, $balance->payoutPending);
        $this->assertSame(0, $balance->paid);
        $this->assertSame(
            ['cash' => 125000, 'payables' => 113000, 'commission_revenue' => 12000],
            $balances->platformTotals(),
        );
    }

    public function test_balances_are_kept_per_store(): void
    {
        $other = $this->shop('store-b');
        $this->postedOrder($this->store, [[100000, 1, 10]]);
        $this->postedOrder($other, [[50000, 1, 10]]);

        $balances = app(BalanceService::class);

        $this->assertSame(90000, $balances->forStore($this->store->id)->pending);
        $this->assertSame(45000, $balances->forStore($other->id)->pending);
    }
}
