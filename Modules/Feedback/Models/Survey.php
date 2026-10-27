<?php

namespace Modules\Feedback\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\BelongsToStore;
use Modules\Sales\Models\Order;
use Modules\Tenancy\Models\Store;

/**
 * A Customer rating of a delivered Order, one per Order. Created at delivery, invited later, answered once.
 */
#[Fillable([
    'store_id', 'order_id', 'token', 'overall_rating', 'product_match_rating', 'delivery_speed_rating',
    'comment', 'sent_at', 'submitted_at', 'low_rating_flag',
])]
class Survey extends Model
{
    use BelongsToStore;

    protected $table = 'satisfaction_surveys';

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'overall_rating' => 'integer',
            'product_match_rating' => 'integer',
            'delivery_speed_rating' => 'integer',
            'sent_at' => 'datetime',
            'submitted_at' => 'datetime',
            'low_rating_flag' => 'boolean',
        ];
    }
}
