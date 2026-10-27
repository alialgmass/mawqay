<?php

namespace Modules\Ledger\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\BelongsToStore;
use Modules\Core\Money\Money;
use Modules\Ledger\Enums\PayoutMethod;
use Modules\Ledger\Enums\PayoutStatus;
use Modules\Tenancy\Models\Store;

/**
 * A Vendor's request for Available money. The destination is a snapshot taken at request time,
 * encrypted at rest, so editing the saved details never redirects a transfer in progress.
 */
#[Fillable([
    'store_id', 'amount', 'status', 'transfer_method', 'destination', 'transfer_reference', 'proof_path',
    'requested_by', 'requested_at', 'processed_by', 'processed_at', 'reject_reason', 'cancelled_by', 'cancelled_at',
])]
class Payout extends Model
{
    use BelongsToStore;

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function money(): Money
    {
        return Money::fromPiasters((int) $this->amount);
    }

    public function isOpen(): bool
    {
        return $this->status === PayoutStatus::Requested;
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'status' => PayoutStatus::class,
            'transfer_method' => PayoutMethod::class,
            'destination' => 'encrypted',
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
