<?php

namespace Modules\Catalog\Filament\Vendor\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Modules\Catalog\Models\Product;

class LowStockWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('catalog::products.widgets.low_stock_heading'))
            ->query(fn (): Builder => Product::query()
                ->withSum('variants', 'stock')
                ->whereRaw(
                    '(select coalesce(sum(stock), 0) from product_variants where product_variants.product_id = products.id) <= ?',
                    [Product::LOW_STOCK_THRESHOLD],
                ))
            ->columns([
                TextColumn::make('name')->label(__('catalog::products.name')),
                TextColumn::make('variants_sum_stock')->label(__('catalog::products.total_stock'))->default(0)->badge()->color('danger'),
            ])
            ->paginated(false);
    }
}
