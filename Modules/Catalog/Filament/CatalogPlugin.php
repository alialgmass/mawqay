<?php

namespace Modules\Catalog\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Catalog\Filament\Admin\Resources\Categories\CategoryResource as AdminCategoryResource;
use Modules\Catalog\Filament\Admin\Resources\Products\ProductResource as AdminProductResource;
use Modules\Catalog\Filament\Vendor\Resources\Categories\CategoryResource as VendorCategoryResource;
use Modules\Catalog\Filament\Vendor\Resources\Products\ProductResource as VendorProductResource;
use Modules\Catalog\Filament\Vendor\Widgets\CatalogStatsWidget;
use Modules\Catalog\Filament\Vendor\Widgets\LowStockWidget;

/**
 * Contributes the Catalog resources to the Admin and Vendor panels.
 */
class CatalogPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'catalog';
    }

    public function register(Panel $panel): void
    {
        match ($panel->getId()) {
            'admin' => $panel->resources([AdminProductResource::class, AdminCategoryResource::class]),
            'vendor' => $panel
                ->resources([VendorProductResource::class, VendorCategoryResource::class])
                ->widgets([CatalogStatsWidget::class, LowStockWidget::class]),
            default => null,
        };
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
