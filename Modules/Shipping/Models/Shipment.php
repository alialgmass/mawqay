<?php

namespace Modules\Shipping\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\BelongsToStore;
use Modules\Sales\Models\Order;

#[Fillable(['store_id', 'order_id', 'courier_name', 'tracking_number', 'tracking_url', 'note', 'shipped_at', 'delivered_at'])]
class Shipment extends Model
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
        return ['shipped_at' => 'datetime', 'delivered_at' => 'datetime'];
    }
}
