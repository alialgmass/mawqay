<?php

namespace Modules\Payments\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\BelongsToStore;
use Modules\Core\Money\Money;
use Modules\Payments\Database\Factories\PaymentFactory;
use Modules\Payments\Enums\PaymentStatus;
use Modules\Sales\Models\Order;
use Modules\Tenancy\Models\Store;

/**
 * A Customer's claim to have paid an Order, with the Receipt that backs it.
 * One row per submission.
 */
#[Fillable([
    'store_id', 'order_id', 'method', 'amount', 'status', 'attempt', 'receipt_path', 'receipt_mime',
    'transfer_reference', 'sender_phone', 'method_snapshot', 'reviewed_by', 'reviewed_at',
    'rejection_reason', 'duplicate_of_id',
])]
class Payment extends Model
{
    use BelongsToStore;

    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

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

    public function amount(): Money
    {
        return Money::fromPiasters((int) $this->amount);
    }

    public function isDuplicate(): bool
    {
        return $this->duplicate_of_id !== null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'attempt' => 'integer',
            'method_snapshot' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): PaymentFactory
    {
        return PaymentFactory::new();
    }
}
