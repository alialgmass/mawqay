<?php

namespace Modules\Tenancy\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Tenancy\Filament\Admin\Resources\Stores\StoreResource;

/**
 * The reference module plugin: each module contributes its Filament resources
 * to the panels through a plugin like this one.
 */
class TenancyPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'tenancy';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            StoreResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
