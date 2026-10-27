<?php

namespace Modules\Sales\Filament\Vendor\Resources\Orders;

use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Money\Money;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Filament\Vendor\Resources\Orders\Pages\ListOrders;
use Modules\Sales\Filament\Vendor\Resources\Orders\Pages\ViewOrder;
use Modules\Sales\Filament\Vendor\Resources\Orders\Support\FulfilmentActions;
use Modules\Sales\Models\Order;
use Modules\Sales\Models\OrderItem;

/**
 * A Vendor Owner's read-only list of their own Store's Orders, and only
 * once they are paid for. Orders that may never be paid stay out of sight.
 */
class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    public static function getModelLabel(): string
    {
        return __('sales::orders.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('sales::orders.plural');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    /**
     * @return Builder<Order>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleToVendor();
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextEntry::make('number')->label(__('sales::orders.number')),
                TextEntry::make('status')
                    ->label(__('sales::orders.status'))
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => __('sales::orders.statuses.'.$state->value)),
                TextEntry::make('created_at')->label(__('sales::orders.placed_at'))->dateTime(),
                TextEntry::make('subtotal')->label(__('sales::orders.subtotal'))->formatStateUsing(fn (int $state): string => Money::fromPiasters($state)->format()),
                TextEntry::make('shipping_fee')->label(__('sales::orders.shipping_fee'))->formatStateUsing(fn (int $state): string => Money::fromPiasters($state)->format()),
                TextEntry::make('total')->label(__('sales::orders.total'))->formatStateUsing(fn (int $state): string => Money::fromPiasters($state)->format()),
            ]),
            Section::make(__('sales::orders.items'))->schema([
                RepeatableEntry::make('item_rows')
                    ->hiddenLabel()
                    ->state(fn (Order $record): array => $record->items->map(fn (OrderItem $item): array => [
                        'product' => $item->product_name.' ('.$item->size.' / '.$item->color.')',
                        'quantity' => $item->quantity,
                        'line_total' => $item->lineTotal()->format(),
                    ])->all())
                    ->columns(3)
                    ->schema([
                        TextEntry::make('product')->label(__('sales::orders.item.product')),
                        TextEntry::make('quantity')->label(__('sales::orders.item.quantity')),
                        TextEntry::make('line_total')->label(__('sales::orders.item.line_total')),
                    ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->label(__('sales::orders.number'))->searchable(),
                TextColumn::make('status')
                    ->label(__('sales::orders.status'))
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => __('sales::orders.statuses.'.$state->value)),
                TextColumn::make('total')->label(__('sales::orders.total'))->formatStateUsing(fn (int $state): string => Money::fromPiasters($state)->format()),
                TextColumn::make('created_at')->label(__('sales::orders.placed_at'))->dateTime()->sortable(),
            ])
            ->recordActions([ViewAction::make(), ...FulfilmentActions::all()])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
