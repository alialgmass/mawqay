<?php

namespace Modules\Ledger\Services;

use Illuminate\Support\Facades\DB;
use Modules\Ledger\Enums\EntryDirection;
use Modules\Ledger\Enums\LedgerAccount;
use Modules\Ledger\Models\Payout;

/**
 * The balanced entry groups a Payout writes: asked for, paid, or handed back. Each group is keyed by
 * the Payout and the step, so repeating a step posts nothing.
 */
class PayoutPoster
{
    public function __construct(private readonly Poster $poster) {}

    /**
     * Available money becomes Payout Pending.
     */
    public function request(Payout $payout): void
    {
        $this->poster->post("payout:{$payout->id}:request", [
            $this->line('available', LedgerAccount::VendorPayableAvailable, EntryDirection::Debit, $payout, 'payout_request'),
            $this->line('pending', LedgerAccount::VendorPayoutPending, EntryDirection::Credit, $payout, 'payout_request'),
        ]);
    }

    /**
     * Payout Pending leaves with the transfer: the Vendor is owed less and the Platform holds less cash.
     */
    public function paid(Payout $payout): void
    {
        $this->poster->post("payout:{$payout->id}:paid", [
            $this->line('pending', LedgerAccount::VendorPayoutPending, EntryDirection::Debit, $payout, 'payout_paid'),
            [...$this->line('cash', LedgerAccount::PlatformCash, EntryDirection::Credit, $payout, 'payout_paid'), 'store_id' => null],
        ]);
    }

    /**
     * Payout Pending goes back to Available, pointing at the entries it undoes.
     *
     * @param  string  $step  cancel or reject
     */
    public function handBack(Payout $payout, string $step): void
    {
        $type = "payout_{$step}";

        $this->poster->post("payout:{$payout->id}:{$step}", [
            [...$this->line('pending', LedgerAccount::VendorPayoutPending, EntryDirection::Debit, $payout, $type), 'reverses_entry_id' => $this->original($payout, LedgerAccount::VendorPayoutPending)],
            [...$this->line('available', LedgerAccount::VendorPayableAvailable, EntryDirection::Credit, $payout, $type), 'reverses_entry_id' => $this->original($payout, LedgerAccount::VendorPayableAvailable)],
        ]);
    }

    /**
     * @return array{key: string, account: LedgerAccount, direction: EntryDirection, amount: int, store_id: int, payout_id: int, meta: array<string, mixed>}
     */
    private function line(string $key, LedgerAccount $account, EntryDirection $direction, Payout $payout, string $type): array
    {
        return [
            'key' => $key,
            'account' => $account,
            'direction' => $direction,
            'amount' => $payout->amount,
            'store_id' => $payout->store_id,
            'payout_id' => $payout->id,
            'meta' => ['type' => $type, 'payout_id' => $payout->id],
        ];
    }

    private function original(Payout $payout, LedgerAccount $account): ?int
    {
        $id = DB::table('ledger_entries')->where('posting_group', "payout:{$payout->id}:request")->where('account', $account->value)->value('id');

        return $id === null ? null : (int) $id;
    }
}
