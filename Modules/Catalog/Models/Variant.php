<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Database\Factories\VariantFactory;
use Modules\Core\Concerns\BelongsToStore;
use Modules\Core\Money\Money;

#[Fillable(['store_id', 'product_id', 'sku', 'size', 'color', 'price_override', 'stock', 'reserved'])]
class Variant extends Model
{
    use BelongsToStore;

    /** @use HasFactory<VariantFactory> */
    use HasFactory;

    protected $table = 'product_variants';

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * What can still be bought: stock that is not held by a reservation.
     */
    public function available(): int
    {
        return max(0, $this->stock - $this->reserved);
    }

    public function isAvailable(): bool
    {
        return $this->available() > 0;
    }

    /**
     * The override if there is one, otherwise the Product current price.
     */
    public function price(): Money
    {
        return $this->price_override !== null
            ? Money::fromPiasters((int) $this->price_override)
            : $this->product->currentPrice();
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'price_override' => 'integer',
            'stock' => 'integer',
            'reserved' => 'integer',
        ];
    }

    protected static function newFactory(): VariantFactory
    {
        return VariantFactory::new();
    }
}
