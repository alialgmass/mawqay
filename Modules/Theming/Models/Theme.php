<?php

namespace Modules\Theming\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Modules\Theming\Database\Factories\ThemeFactory;
use Modules\Theming\Support\ThemeManifest;

#[Fillable(['slug', 'name', 'version', 'is_active', 'manifest'])]
class Theme extends Model
{
    /** @use HasFactory<ThemeFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (self $theme): void {
            if ($theme->isDefault()) {
                throw ValidationException::withMessages(['theme' => __('theming::themes.errors.default_always_active')]);
            }
        });
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function manifest(): ThemeManifest
    {
        return ThemeManifest::fromArray($this->manifest);
    }

    public function isDefault(): bool
    {
        return $this->slug === config('theming.default');
    }

    protected static function newFactory(): ThemeFactory
    {
        return ThemeFactory::new();
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'manifest' => 'array',
        ];
    }
}
