<?php

namespace Modules\Ledger\Services;

use Illuminate\Support\Facades\DB;
use Modules\Ledger\Enums\EntryDirection;
use Modules\Ledger\Enums\LedgerAccount;
use Modules\Ledger\Exceptions\ReversalNotPossible;
use Modules\Ledger\Models\LedgerEntry;
use Modules\Sales\Models\Order;

/**
 * Undoes money by writing opposite entries that point at the originals. A partial amount
 * takes Commission in proportion, rounded half-up, with the Vendor bearing the remainder.
 * The same reference reverses once.
 */
class ReversalService
{
    public function __construct(private readonly Poster $poster, private readonly BalanceService $balances) {}

    /**
     * @param  int|null  $amount  piasters handed back to the Customer; null reverses everything still held
     * @param  string  $reference  names this reversal so repeating it changes nothing, e.g. "refund:12"
     * @return list<LedgerEntry> the entries written; empty when this reference was already reversed
     *
     * @throws ReversalNotPossible
     */
    public function reverse(Order $order, ?int $amount, string $reference): array
    {
        return DB::transaction(function () use ($order, $amount, $reference): array {
            $group = "reversal:{$reference}";

            if ($this->poster->alreadyPosted("{$group}:cash")) {
                return [];
            }

            $net = $this->balances->forOrder($order->id);
            $held = $net[LedgerAccount::PlatformCash->value] ?? 0;
            $commissionHeld = $net[LedgerAccount::PlatformCommissionRevenue->value] ?? 0;
            $pending = $net[LedgerAccount::VendorPayablePending->value] ?? 0;
            $available = $net[LedgerAccount::VendorPayableAvailable->value] ?? 0;

            if ($held <= 0) {
                throw new ReversalNotPossible('Nothing is left to reverse on this Order.');
            }

            $amount ??= $held;

            if ($amount <= 0 || $amount > $held) {
                throw new ReversalNotPossible("Cannot reverse {$amount} when {$held} is held.");
            }

            $commission = $amount === $held ? $commissionHeld : intdiv($commissionHeld * $amount * 2 + $held, $held * 2);
            $vendor = $amount - $commission;

            if ($vendor > $pending + $available || $commission > $commissionHeld) {
                throw new ReversalNotPossible('The Vendor balance on this Order cannot cover the reversal.');
            }

            $fromPending = min($vendor, $pending);
            $fromAvailable = $vendor - $fromPending;
            $meta = ['type' => 'reversal', 'reference' => $reference, 'order_number' => $order->number];
            $line = fn (string $key, LedgerAccount $account, EntryDirection $direction, int $value, bool $forStore): array => [
                'key' => $key,
                'account' => $account,
                'direction' => $direction,
                'amount' => $value,
                'store_id' => $forStore ? $order->store_id : null,
                'order_id' => $order->id,
                'reverses_entry_id' => $this->original($order, $account, $direction->opposite()),
                'meta' => $meta,
            ];

            return $this->poster->post($group, [
                $line('cash', LedgerAccount::PlatformCash, EntryDirection::Credit, $amount, false),
                $line('commission', LedgerAccount::PlatformCommissionRevenue, EntryDirection::Debit, $commission, false),
                $line('pending', LedgerAccount::VendorPayablePending, EntryDirection::Debit, $fromPending, true),
                $line('available', LedgerAccount::VendorPayableAvailable, EntryDirection::Debit, $fromAvailable, true),
            ]);
        });
    }

    /**
     * The first entry that put money into the account on this Order.
     */
    private function original(Order $order, LedgerAccount $account, EntryDirection $direction): ?int
    {
        $id = DB::table('ledger_entries')
            ->where('order_id', $order->id)
            ->where('account', $account->value)
            ->where('direction', $direction->value)
            ->whereNull('reverses_entry_id')
            ->orderBy('id')
            ->value('id');

        return $id === null ? null : (int) $id;
    }
}
