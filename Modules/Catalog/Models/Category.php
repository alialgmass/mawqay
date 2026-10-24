<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Database\Factories\CategoryFactory;
use Modules\Core\Concerns\BelongsToStore;
use Modules\Tenancy\Models\Store;

#[Fillable(['store_id', 'parent_id', 'name', 'slug', 'sort', 'is_active'])]
class Category extends Model
{
    use BelongsToStore;

    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $category): void {
            if ($category->parent_id === null) {
                return;
            }

            $parent = self::query()->find($category->parent_id);

            if ($parent === null || $parent->parent_id !== null || $parent->is($category)) {
                throw ValidationException::withMessages(['parent_id' => __('catalog::categories.errors.two_levels')]);
            }

            if ($category->exists && $category->children()->exists()) {
                throw ValidationException::withMessages(['parent_id' => __('catalog::categories.errors.two_levels')]);
            }
        });

        static::deleting(function (self $category): void {
            if ($category->products()->exists() || $category->children()->exists()) {
                throw ValidationException::withMessages(['category' => __('catalog::categories.errors.in_use')]);
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
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeTopLevel(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    /**
     * Active itself and under an active parent, if it has one.
     *
     * @param  Builder<self>  $query
     */
    public function scopeShown(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('parent_id')
                ->orWhereHas('parent', fn (Builder $parent) => $parent->where('is_active', true)));
    }

    /**
     * The id of this Category and, for a top-level one, of its sub-Categories.
     *
     * @return list<int>
     */
    public function selfAndChildIds(): array
    {
        return [$this->id, ...$this->children()->pluck('id')->all()];
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }
}
