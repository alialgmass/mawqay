<?php

namespace Modules\Ledger\Models;

use Illuminate\Database\Eloquent\Builder;
use Modules\Ledger\Exceptions\LedgerEntryIsImmutable;

/**
 * Closes the bulk routes around the model guard: no query may rewrite or remove entries.
 *
 * @extends Builder<LedgerEntry>
 */
class ImmutableEntryBuilder extends Builder
{
    public function update(array $values): never
    {
        throw new LedgerEntryIsImmutable;
    }

    public function upsert(array $values, $uniqueBy, $update = null): never
    {
        throw new LedgerEntryIsImmutable;
    }

    public function increment($column, $amount = 1, array $extra = []): never
    {
        throw new LedgerEntryIsImmutable;
    }

    public function decrement($column, $amount = 1, array $extra = []): never
    {
        throw new LedgerEntryIsImmutable;
    }

    public function delete(): never
    {
        throw new LedgerEntryIsImmutable;
    }

    public function forceDelete(): never
    {
        throw new LedgerEntryIsImmutable;
    }
}
