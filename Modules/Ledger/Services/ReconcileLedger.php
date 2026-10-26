<?php

namespace Modules\Ledger\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Enums\UserRole;
use Modules\Core\Support\StoreContext;
use Modules\Ledger\Enums\LedgerAccount;
use Modules\Ledger\Enums\ReconciliationStatus;
use Modules\Ledger\Models\Reconciliation;
use Modules\Ledger\Notifications\ReconciliationFailed;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Models\Order;
use Modules\Tenancy\Models\Store;

/**
 * Proves the books agree with themselves and with the Orders, stores the result, and tells
 * every Platform Admin when they do not.
 *
 * @phpstan-type Mismatch array{type: string, message: string, order_number?: string, expected?: int, actual?: int}
 */
class ReconcileLedger
{
    public function __construct(private readonly BalanceService $balances, private readonly StoreContext $context) {}

    public function __invoke(?int $ranBy = null): Reconciliation
    {
        $totals = DB::table('ledger_entries')
            ->selectRaw("coalesce(sum(case when direction = 'debit' then amount else 0 end), 0) as debits, coalesce(sum(case when direction = 'credit' then amount else 0 end), 0) as credits")
            ->first();
        $debits = (int) $totals->debits;
        $credits = (int) $totals->credits;
        $platform = $this->balances->platformTotals();

        $mismatches = [];

        if ($debits !== $credits) {
            $mismatches[] = ['type' => 'totals', 'message' => 'Total debits differ from total credits.', 'expected' => $debits, 'actual' => $credits];
        }

        foreach ($this->unbalancedGroups() as $group => $difference) {
            $mismatches[] = ['type' => 'group', 'message' => "Posting group {$group} does not balance.", 'expected' => 0, 'actual' => $difference];
        }

        if ($platform['cash'] !== $platform['payables'] + $platform['commission_revenue']) {
            $mismatches[] = ['type' => 'cash', 'message' => 'Platform cash differs from Vendor payables plus Commission revenue.', 'expected' => $platform['cash'], 'actual' => $platform['payables'] + $platform['commission_revenue']];
        }

        array_push($mismatches, ...$this->orderMismatches());

        $run = Reconciliation::query()->create([
            'status' => $mismatches === [] ? ReconciliationStatus::Passed : ReconciliationStatus::Failed,
            'total_debits' => $debits,
            'total_credits' => $credits,
            'platform_cash' => $platform['cash'],
            'payables' => $platform['payables'],
            'commission_revenue' => $platform['commission_revenue'],
            'mismatches' => $mismatches === [] ? null : $mismatches,
            'ran_by' => $ranBy,
        ]);

        if ($mismatches !== []) {
            User::query()
                ->where('role', UserRole::Admin->value)
                ->get()
                ->each(fn (User $admin) => $admin->notify(new ReconciliationFailed(count($mismatches), url('/admin'))));
        }

        return $run;
    }

    /**
     * @return array<string, int> posting group => debits minus credits
     */
    private function unbalancedGroups(): array
    {
        return DB::table('ledger_entries')
            ->selectRaw("posting_group, sum(case when direction = 'debit' then amount else -amount end) as difference")
            ->groupBy('posting_group')
            ->havingRaw('sum(case when direction = \'debit\' then amount else -amount end) <> 0')
            ->pluck('difference', 'posting_group')
            ->map(fn ($difference): int => (int) $difference)
            ->all();
    }

    /**
     * @return list<Mismatch>
     */
    private function orderMismatches(): array
    {
        $net = [];

        foreach (DB::table('ledger_entries')
            ->whereNotNull('order_id')
            ->selectRaw("order_id, account, sum(case when direction = 'credit' then amount else -amount end) as credit_net")
            ->groupBy('order_id', 'account')
            ->get() as $row) {
            $account = LedgerAccount::from($row->account);
            $net[(int) $row->order_id][$account->value] = $account->isDebitNormal() ? -(int) $row->credit_net : (int) $row->credit_net;
        }

        $reversed = DB::table('ledger_entries')
            ->where('account', LedgerAccount::PlatformCash->value)
            ->whereNotNull('reverses_entry_id')
            ->selectRaw('order_id, sum(amount) as reversed')
            ->groupBy('order_id')
            ->pluck('reversed', 'order_id')
            ->map(fn ($amount): int => (int) $amount)
            ->all();

        $mismatches = [];

        foreach (Store::query()->pluck('id') as $storeId) {
            $this->context->runAs($storeId, function () use ($net, $reversed, &$mismatches): void {
                Order::query()->withSum('items as commission_total', 'commission_amount')->each(function (Order $order) use ($net, $reversed, &$mismatches): void {
                    array_push($mismatches, ...$this->checkOrder($order, $net[$order->id] ?? [], $reversed[$order->id] ?? 0));
                });
            });
        }

        return $mismatches;
    }

    /**
     * @param  array<string, int>  $net
     * @param  int  $reversed  cash handed back so far through reversals
     * @return list<Mismatch>
     */
    private function checkOrder(Order $order, array $net, int $reversed): array
    {
        $cash = $net[LedgerAccount::PlatformCash->value] ?? 0;
        $commission = $net[LedgerAccount::PlatformCommissionRevenue->value] ?? 0;
        $pending = $net[LedgerAccount::VendorPayablePending->value] ?? 0;
        $available = $net[LedgerAccount::VendorPayableAvailable->value] ?? 0;
        $payoutPending = $net[LedgerAccount::VendorPayoutPending->value] ?? 0;
        $vendor = $pending + $available + $payoutPending;

        $found = [];
        $check = function (string $what, int $expected, int $actual) use ($order, &$found): void {
            if ($expected !== $actual) {
                $found[] = ['type' => 'order', 'message' => "Order {$order->number}: {$what} is {$actual}, expected {$expected}.", 'order_number' => $order->number, 'expected' => $expected, 'actual' => $actual];
            }
        };

        match ($order->status) {
            OrderStatus::PendingPayment, OrderStatus::PaymentReview, OrderStatus::Cancelled => $check('money held for an unpaid Order', 0, $cash + $commission + $vendor),
            OrderStatus::Refunded => $check('Vendor money plus Commission against cash', $cash, $commission + $vendor),
            default => (function () use ($check, $order, $cash, $commission, $vendor, $pending, $available, $reversed): void {
                $check('cash received less reversals', (int) $order->total - $reversed, $cash);
                $check('Vendor money plus Commission against cash', $cash, $commission + $vendor);

                if ($reversed === 0) {
                    $check('Commission', (int) $order->commission_total, $commission);
                } else {
                    $check('Commission within what was earned', min($commission, (int) $order->commission_total), $commission);
                }

                if ($order->status === OrderStatus::Completed) {
                    $check('Pending money on a completed Order', 0, $pending);
                } else {
                    $check('Available money before completion', 0, $available);
                }
            })(),
        };

        return $found;
    }
}
