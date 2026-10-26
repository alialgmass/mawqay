<?php

namespace Modules\Ledger\Services;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Modules\Ledger\Enums\EntryDirection;
use Modules\Ledger\Enums\LedgerAccount;
use Modules\Ledger\Exceptions\UnbalancedPosting;
use Modules\Ledger\Models\LedgerEntry;

/**
 * The only way entries are written: a whole posting group goes in together, in
 * balance, or not at all. Posting the same group twice writes nothing the second time.
 *
 * @phpstan-type Line array{key: string, account: LedgerAccount, direction: EntryDirection, amount: int, store_id?: int|null, order_id?: int|null, payout_id?: int|null, reverses_entry_id?: int|null, available_at?: \DateTimeInterface|null, meta?: array<string, mixed>|null}
 */
class Poster
{
    /**
     * @param  list<Line>  $lines  zero-amount lines are dropped
     * @return list<LedgerEntry> the entries written; empty when the group was already posted
     *
     * @throws UnbalancedPosting
     */
    public function post(string $group, array $lines): array
    {
        $lines = array_values(array_filter($lines, fn (array $line): bool => $line['amount'] > 0));

        if ($lines === []) {
            return [];
        }

        $debits = $this->sum($lines, EntryDirection::Debit);
        $credits = $this->sum($lines, EntryDirection::Credit);

        if ($debits !== $credits) {
            throw UnbalancedPosting::for($group, $debits, $credits);
        }

        try {
            return DB::transaction(function () use ($group, $lines): array {
                if ($this->alreadyPosted("{$group}:{$lines[0]['key']}")) {
                    return [];
                }

                return array_map(fn (array $line): LedgerEntry => LedgerEntry::query()->create([
                    'store_id' => $line['store_id'] ?? null,
                    'order_id' => $line['order_id'] ?? null,
                    'payout_id' => $line['payout_id'] ?? null,
                    'posting_group' => $group,
                    'account' => $line['account'],
                    'direction' => $line['direction'],
                    'amount' => $line['amount'],
                    'reverses_entry_id' => $line['reverses_entry_id'] ?? null,
                    'available_at' => $line['available_at'] ?? null,
                    'idempotency_key' => "{$group}:{$line['key']}",
                    'meta' => $line['meta'] ?? null,
                ]), $lines);
            });
        } catch (UniqueConstraintViolationException) {
            // A concurrent run posted the same group between our check and our insert.
            return [];
        }
    }

    public function alreadyPosted(string $idempotencyKey): bool
    {
        return DB::table('ledger_entries')->where('idempotency_key', $idempotencyKey)->exists();
    }

    /**
     * @param  list<Line>  $lines
     */
    private function sum(array $lines, EntryDirection $direction): int
    {
        return array_sum(array_map(fn (array $line): int => $line['direction'] === $direction ? $line['amount'] : 0, $lines));
    }
}
