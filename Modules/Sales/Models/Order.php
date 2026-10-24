<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Auth\Models\Customer;
use Modules\Core\Concerns\BelongsToStore;
use Modules\Core\Money\Money;
use Modules\Sales\Database\Factories\OrderFactory;
use Modules\Sales\Enums\OrderStatus;
use Modules\Tenancy\Models\Store;

#[Fillable([
    'store_id', 'number', 'customer_id', 'status', 'subtotal', 'shipping_fee', 'total', 'currency',
    'payment_method', 'shipping_address', 'customer_snapshot', 'expires_at', 'delivered_at',
    'completed_at', 'cancelled_at',
])]
class Order extends Model
{
    use BelongsToStore;

    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    /**
     * @return HasMany<OrderStatusLog, $this>
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class)->orderBy('id');
    }

    /**
     * Orders a Vendor may see: paid for, or later.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisibleToVendor(Builder $query): void
    {
        $query->whereIn('status', array_map(fn (OrderStatus $status): string => $status->value, OrderStatus::visibleToVendor()));
    }

    public function total(): Money
    {
        return Money::fromPiasters((int) $this->total);
    }

    public function subtotal(): Money
    {
        return Money::fromPiasters((int) $this->subtotal);
    }

    public function shippingFee(): Money
    {
        return Money::fromPiasters((int) $this->shipping_fee);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'integer',
            'shipping_fee' => 'integer',
            'total' => 'integer',
            'shipping_address' => 'array',
            'customer_snapshot' => 'array',
            'expires_at' => 'datetime',
            'delivered_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }
}
