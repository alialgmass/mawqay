<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Concerns\BelongsToStore;
use Modules\Sales\Enums\OrderStatus;

#[Fillable(['store_id', 'order_id', 'from_status', 'to_status', 'actor_type', 'actor_id', 'note'])]
class OrderStatusLog extends Model
{
    use BelongsToStore;

    public const UPDATED_AT = null;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['from_status' => OrderStatus::class, 'to_status' => OrderStatus::class];
    }
}
