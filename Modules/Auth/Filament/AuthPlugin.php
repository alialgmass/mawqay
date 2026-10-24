<?php

namespace Modules\Auth\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Auth\Filament\Admin\Resources\Customers\CustomerResource as AdminCustomerResource;
use Modules\Auth\Filament\Vendor\Resources\Customers\CustomerResource as VendorCustomerResource;

/**
 * Contributes the read-only Customer lists to the Admin and Vendor panels.
 */
class AuthPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'auth';
    }

    public function register(Panel $panel): void
    {
        match ($panel->getId()) {
            'admin' => $panel->resources([AdminCustomerResource::class]),
            'vendor' => $panel->resources([VendorCustomerResource::class]),
            default => null,
        };
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
