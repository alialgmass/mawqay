<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Models\Variant;
use Modules\Core\Concerns\BelongsToStore;
use Modules\Core\Money\Money;

/**
 * One Variant and quantity in an Order, frozen at purchase time together
 * with the Commission Rate and Amount.
 */
#[Fillable([
    'store_id', 'order_id', 'variant_id', 'product_name', 'sku', 'size', 'color', 'unit_price',
    'quantity', 'line_total', 'commission_rate', 'commission_amount',
])]
class OrderItem extends Model
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
     * @return BelongsTo<Variant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    public function lineTotal(): Money
    {
        return Money::fromPiasters((int) $this->line_total);
    }

    public function unitPrice(): Money
    {
        return Money::fromPiasters((int) $this->unit_price);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'quantity' => 'integer',
            'line_total' => 'integer',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'integer',
        ];
    }
}
