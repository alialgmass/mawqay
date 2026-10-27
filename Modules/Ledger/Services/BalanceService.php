<?php

namespace Modules\Ledger\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Ledger\Enums\LedgerAccount;
use Modules\Ledger\Support\StoreBalance;

/**
 * Balances worked out from entries alone. A Store's figures are cached and the
 * cache is dropped whenever an entry is written for that Store.
 */
class BalanceService
{
    public function forStore(int $storeId): StoreBalance
    {
        $values = Cache::rememberForever($this->key($storeId), function () use ($storeId): array {
            $net = $this->netByAccount(fn ($query) => $query->where('store_id', $storeId));

            return [
                'pending' => $net[LedgerAccount::VendorPayablePending->value] ?? 0,
                'available' => $net[LedgerAccount::VendorPayableAvailable->value] ?? 0,
                'payout_pending' => $net[LedgerAccount::VendorPayoutPending->value] ?? 0,
                'paid' => $this->paidOut(fn ($query) => $query->where('store_id', $storeId)),
            ];
        });

        return StoreBalance::fromArray($values);
    }

    /**
     * Cash held, what is owed to Vendors in every state, and the Commission earned.
     *
     * @return array{cash: int, payables: int, commission_revenue: int}
     */
    public function platformTotals(): array
    {
        $net = $this->netByAccount();

        return [
            'cash' => $net[LedgerAccount::PlatformCash->value] ?? 0,
            'payables' => array_sum(array_map(fn (LedgerAccount $account): int => $net[$account->value] ?? 0, LedgerAccount::vendorPayables())),
            'commission_revenue' => $net[LedgerAccount::PlatformCommissionRevenue->value] ?? 0,
        ];
    }

    /**
     * What one Order still holds in each account, keyed by account value.
     *
     * @return array<string, int>
     */
    public function forOrder(int $orderId): array
    {
        return $this->netByAccount(fn ($query) => $query->where('order_id', $orderId));
    }

    public function forget(?int $storeId): void
    {
        if ($storeId !== null) {
            Cache::forget($this->key($storeId));
        }
    }

    /**
     * Normal-side balance per account: debits less credits for cash, credits less debits for the rest.
     *
     * @param  (callable(Builder): mixed)|null  $constrain
     * @return array<string, int>
     */
    public function netByAccount(?callable $constrain = null): array
    {
        $query = DB::table('ledger_entries')
            ->selectRaw("account, sum(case when direction = 'credit' then amount else -amount end) as credit_net")
            ->groupBy('account');

        if ($constrain !== null) {
            $constrain($query);
        }

        $net = [];

        foreach ($query->get() as $row) {
            $account = LedgerAccount::from($row->account);
            $net[$account->value] = $account->isDebitNormal() ? -(int) $row->credit_net : (int) $row->credit_net;
        }

        return $net;
    }

    /**
     * What has actually been transferred to Vendors: the Payout Pending entries cleared by a paid Payout.
     *
     * @param  (callable(Builder): mixed)|null  $constrain
     */
    public function paidOut(?callable $constrain = null): int
    {
        $query = DB::table('ledger_entries')
            ->where('account', LedgerAccount::VendorPayoutPending->value)
            ->where('direction', 'debit')
            ->where('posting_group', 'like', 'payout:%:paid');

        if ($constrain !== null) {
            $constrain($query);
        }

        return (int) $query->sum('amount');
    }

    private function key(int $storeId): string
    {
        return "ledger.balance.{$storeId}";
    }
}
