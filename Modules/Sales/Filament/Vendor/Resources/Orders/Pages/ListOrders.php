<?php

namespace Modules\Sales\Filament\Vendor\Resources\Orders\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Filament\Vendor\Resources\Orders\OrderResource;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $count = fn (array $statuses): int => OrderResource::getEloquentQuery()->whereIn('status', array_map(fn (OrderStatus $status): string => $status->value, $statuses))->count();

        return [
            'all' => Tab::make(__('sales::orders.tabs.all')),
            'to_ship' => Tab::make(__('sales::orders.tabs.to_ship'))
                ->badge($count([OrderStatus::Paid, OrderStatus::Processing]))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('status', [OrderStatus::Paid->value, OrderStatus::Processing->value])),
            'shipped' => Tab::make(__('sales::orders.tabs.shipped'))
                ->badge($count([OrderStatus::Shipped]))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', OrderStatus::Shipped->value)),
        ];
    }
}
