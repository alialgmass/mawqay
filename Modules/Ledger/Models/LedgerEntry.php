<?php

namespace Modules\Ledger\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Modules\Core\Money\Money;
use Modules\Core\Scopes\StoreScope;
use Modules\Ledger\Database\Factories\LedgerEntryFactory;
use Modules\Ledger\Enums\EntryDirection;
use Modules\Ledger\Enums\LedgerAccount;
use Modules\Ledger\Exceptions\LedgerEntryIsImmutable;
use Modules\Ledger\Services\BalanceService;
use Modules\Sales\Models\Order;
use Modules\Tenancy\Models\Store;

/**
 * One line of the Platform books. Append-only: it can be created and read, never changed or removed.
 * Platform-only accounts have no Store, so those rows are invisible inside any Store.
 */
#[Fillable([
    'store_id', 'order_id', 'payout_id', 'posting_group', 'account', 'direction', 'amount',
    'reverses_entry_id', 'available_at', 'idempotency_key', 'meta',
])]
class LedgerEntry extends Model
{
    /** @use HasFactory<LedgerEntryFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::addGlobalScope(new StoreScope);

        static::updating(fn () => throw new LedgerEntryIsImmutable);
        static::deleting(fn () => throw new LedgerEntryIsImmutable);

        static::created(function (self $entry): void {
            $forget = fn () => app(BalanceService::class)->forget($entry->store_id);

            $forget();
            DB::afterCommit($forget);
        });
    }

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function money(): Money
    {
        return Money::fromPiasters((int) $this->amount);
    }

    /**
     * The amount as it changes what is owed: positive for credits, negative for debits.
     */
    public function signedAmount(): int
    {
        return $this->direction === EntryDirection::Credit ? (int) $this->amount : -(int) $this->amount;
    }

    /**
     * Adds each row's running total of what the Store is owed, in entry order, as `running_balance`.
     *
     * @param  Builder<self>  $query
     */
    public function scopeWithRunningBalance(Builder $query): void
    {
        $query->addSelect([
            $query->qualifyColumn('*'),
            DB::raw("(select coalesce(sum(case when e2.direction = 'credit' then e2.amount else -e2.amount end), 0)
                from ledger_entries e2
                where e2.store_id = ledger_entries.store_id and e2.id <= ledger_entries.id) as running_balance"),
        ]);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeVendorAccounts(Builder $query): void
    {
        $query->whereIn('account', array_map(fn (LedgerAccount $account): string => $account->value, LedgerAccount::vendorPayables()));
    }

    public function newEloquentBuilder($query): ImmutableEntryBuilder
    {
        return new ImmutableEntryBuilder($query);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'account' => LedgerAccount::class,
            'direction' => EntryDirection::class,
            'amount' => 'integer',
            'available_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    protected static function newFactory(): LedgerEntryFactory
    {
        return LedgerEntryFactory::new();
    }
}
