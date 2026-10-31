<?php

namespace Modules\Sales\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Sales\Filament\Admin\Pages\PlatformShippingRates;
use Modules\Sales\Filament\Admin\Resources\Orders\OrderResource as AdminOrderResource;
use Modules\Sales\Filament\Vendor\Pages\ShippingRates;
use Modules\Sales\Filament\Vendor\Resources\Orders\OrderResource as VendorOrderResource;

/**
 * Contributes Orders to the Admin Panel, and Orders and Shipping Rates to the Vendor Panel.
 */
class SalesPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'sales';
    }

    public function register(Panel $panel): void
    {
        match ($panel->getId()) {
            'admin' => $panel->resources([AdminOrderResource::class])->pages([PlatformShippingRates::class]),
            'vendor' => $panel->resources([VendorOrderResource::class])->pages([ShippingRates::class]),
            default => null,
        };
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
