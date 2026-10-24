<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Database\Factories\ProductMediaFactory;
use Modules\Catalog\Jobs\DeriveProductImageSizes;
use Modules\Core\Concerns\BelongsToStore;

#[Fillable(['store_id', 'product_id', 'path', 'sort', 'processed_at'])]
class ProductMedia extends Model
{
    use BelongsToStore;

    /** @use HasFactory<ProductMediaFactory> */
    use HasFactory;

    public const MAX_PER_PRODUCT = 8;

    public const SIZES = ['thumb', 'medium', 'large'];

    protected $table = 'product_media';

    protected static function booted(): void
    {
        static::creating(function (self $media): void {
            if (self::query()->where('product_id', $media->product_id)->count() >= self::MAX_PER_PRODUCT) {
                throw ValidationException::withMessages(['media' => __('catalog::products.errors.max_images')]);
            }
        });

        static::created(function (self $media): void {
            DeriveProductImageSizes::dispatch($media->getKey());
        });
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Where a derived size lives, next to the original.
     */
    public function derivedPath(string $size): string
    {
        return dirname($this->path)."/derived/{$this->getKey()}-{$size}.webp";
    }

    /**
     * The derived WebP of the size once it exists, the original until then.
     */
    public function url(string $size = 'medium'): string
    {
        $path = $this->processed_at !== null && in_array($size, self::SIZES, true)
            ? $this->derivedPath($size)
            : $this->path;

        return Storage::disk('public')->url($path);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['processed_at' => 'datetime'];
    }

    protected static function newFactory(): ProductMediaFactory
    {
        return ProductMediaFactory::new();
    }
}
