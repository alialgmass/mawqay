<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Database\Factories\ProductFactory;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Core\Concerns\BelongsToStore;
use Modules\Core\Money\Money;
use Modules\Core\Support\StoreContext;
use Modules\Tenancy\Models\Store;

#[Fillable([
    'store_id', 'category_id', 'name', 'slug', 'description', 'base_price', 'sale_price',
    'status', 'hide_reason', 'sort',
])]
class Product extends Model
{
    use BelongsToStore;

    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /** Stock at or below which a Product counts as low on stock. */
    public const LOW_STOCK_THRESHOLD = 3;

    protected static function booted(): void
    {
        static::saving(function (self $product): void {
            if ($product->isDirty('category_id') && ! DB::table('categories')->where('id', $product->category_id)->where('store_id', $product->store_id ?? app(StoreContext::class)->id())->exists()) {
                throw ValidationException::withMessages(['category_id' => __('catalog::products.errors.foreign_category')]);
            }

            if ($product->sale_price !== null && $product->sale_price >= $product->base_price) {
                throw ValidationException::withMessages(['sale_price' => __('catalog::products.errors.sale_price')]);
            }
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
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<Variant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }

    /**
     * @return HasMany<ProductMedia, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('sort')->orderBy('id');
    }

    /**
     * Products a visitor may see: Published, in a shown Category.
     *
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ProductStatus::Published)
            ->whereHas('category', fn (Builder $category) => $category->shown());
    }

    /**
     * Products with at least one Variant that can still be bought.
     *
     * @param  Builder<self>  $query
     */
    public function scopeInStock(Builder $query): void
    {
        $query->whereHas('variants', fn (Builder $variant) => $variant->whereColumn('stock', '>', 'reserved'));
    }

    public function basePrice(): Money
    {
        return Money::fromPiasters((int) $this->base_price);
    }

    public function salePrice(): ?Money
    {
        return $this->sale_price === null ? null : Money::fromPiasters((int) $this->sale_price);
    }

    public function currentPrice(): Money
    {
        return $this->salePrice() ?? $this->basePrice();
    }

    public function isOnSale(): bool
    {
        return $this->sale_price !== null;
    }

    public function totalStock(): int
    {
        return (int) $this->variants()->sum('stock');
    }

    public function isLowOnStock(): bool
    {
        return $this->totalStock() <= self::LOW_STOCK_THRESHOLD;
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'base_price' => 'integer',
            'sale_price' => 'integer',
        ];
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
