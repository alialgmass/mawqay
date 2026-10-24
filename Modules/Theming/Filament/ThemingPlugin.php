<?php

namespace Modules\Theming\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Theming\Filament\Admin\Resources\Themes\ThemeResource;
use Modules\Theming\Filament\Vendor\Pages\Appearance;

/**
 * Contributes the Theme list to the Admin Panel and the Appearance page to the Vendor Panel.
 */
class ThemingPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'theming';
    }

    public function register(Panel $panel): void
    {
        match ($panel->getId()) {
            'admin' => $panel->resources([ThemeResource::class]),
            'vendor' => $panel->pages([Appearance::class]),
            default => null,
        };
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
