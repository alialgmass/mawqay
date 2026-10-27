<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\BelongsToStore;
use Modules\Sales\Enums\RefundReason;

#[Fillable(['store_id', 'order_id', 'amount', 'reason', 'note', 'restocked', 'restock_items', 'actor_id', 'reference'])]
class Refund extends Model
{
    use BelongsToStore;

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['amount' => 'integer', 'reason' => RefundReason::class, 'restocked' => 'boolean', 'restock_items' => 'array'];
    }
}
