<?php

namespace Modules\Ledger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ledger\Enums\EntryDirection;
use Modules\Ledger\Enums\LedgerAccount;
use Modules\Ledger\Models\LedgerEntry;
use Modules\Tenancy\Models\Store;

/**
 * @extends Factory<LedgerEntry>
 */
class LedgerEntryFactory extends Factory
{
    protected $model = LedgerEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'posting_group' => 'test:'.fake()->unique()->numerify('######'),
            'account' => LedgerAccount::VendorPayablePending,
            'direction' => EntryDirection::Credit,
            'amount' => 10000,
            'idempotency_key' => fn (array $attributes) => $attributes['posting_group'].':'.fake()->unique()->numerify('######'),
        ];
    }
}
