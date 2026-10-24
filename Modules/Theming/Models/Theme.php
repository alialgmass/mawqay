<?php

namespace Modules\Theming\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Modules\Theming\Support\ThemeManifest;

#[Fillable(['slug', 'name', 'version', 'is_active', 'manifest'])]
class Theme extends Model
{
    public function manifest(): ThemeManifest
    {
        return ThemeManifest::fromArray($this->manifest);
    }

    public function isDefault(): bool
    {
        return $this->slug === config('theming.default');
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
