<?php

namespace Modules\Catalog\Filament\Vendor\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Catalog\Models\Product;

class CatalogStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make(__('catalog::products.widgets.products_count'), Product::query()->count()),
        ];
    }
}
