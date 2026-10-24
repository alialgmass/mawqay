<?php

namespace Modules\Sales\Filament\Admin\Resources\Orders;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Money\Money;
use Modules\Core\Support\AdminScope;
use Modules\Sales\Actions\CancelOrder;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Exceptions\InvalidOrderTransition;
use Modules\Sales\Filament\Admin\Resources\Orders\Pages\ListOrders;
use Modules\Sales\Filament\Admin\Resources\Orders\Pages\ViewOrder;
use Modules\Sales\Models\Order;
use Modules\Sales\Models\OrderItem;
use Modules\Sales\Models\OrderStatusLog;
use Modules\Tenancy\Models\Store;

/**
 * The Platform Admin's view of every Store's Orders. Orders are not edited
 * here; an unpaid or under-review Order can be cancelled with a reason.
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
        return AdminScope::on(Order::class)->with('store');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextEntry::make('number')->label(__('sales::orders.number')),
                TextEntry::make('store.name')->label(__('sales::orders.store')),
                TextEntry::make('status')
                    ->label(__('sales::orders.status'))
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => __('sales::orders.statuses.'.$state->value)),
                TextEntry::make('customer_snapshot.name')->label(__('sales::orders.customer')),
                TextEntry::make('payment_method')
                    ->label(__('sales::orders.payment_method'))
                    ->formatStateUsing(fn (string $state): string => __('sales::orders.payment_methods.'.$state)),
                TextEntry::make('created_at')->label(__('sales::orders.placed_at'))->dateTime(),
                TextEntry::make('subtotal')->label(__('sales::orders.subtotal'))->formatStateUsing(fn (int $state): string => Money::fromPiasters($state)->format()),
                TextEntry::make('shipping_fee')->label(__('sales::orders.shipping_fee'))->formatStateUsing(fn (int $state): string => Money::fromPiasters($state)->format()),
                TextEntry::make('total')->label(__('sales::orders.total'))->formatStateUsing(fn (int $state): string => Money::fromPiasters($state)->format()),
                TextEntry::make('shipping_address.address_line')
                    ->label(__('sales::orders.address'))
                    ->state(fn (Order $record): string => collect([
                        $record->shipping_address['recipient_name'] ?? null,
                        $record->shipping_address['phone'] ?? null,
                        $record->shipping_address['governorate_name'] ?? null,
                        $record->shipping_address['city'] ?? null,
                        $record->shipping_address['address_line'] ?? null,
                    ])->filter()->implode('، '))
                    ->columnSpanFull(),
            ]),
            Section::make(__('sales::orders.items'))->schema([
                RepeatableEntry::make('item_rows')
                    ->hiddenLabel()
                    ->state(fn (Order $record): array => AdminScope::on(OrderItem::class)->where('order_id', $record->id)->orderBy('id')->get()->map(fn (OrderItem $item): array => [
                        'product' => $item->product_name.' ('.$item->size.' / '.$item->color.')',
                        'sku' => $item->sku,
                        'quantity' => $item->quantity,
                        'unit_price' => Money::fromPiasters($item->unit_price)->format(),
                        'line_total' => $item->lineTotal()->format(),
                        'commission' => $item->commission_rate.'% = '.Money::fromPiasters($item->commission_amount)->format(),
                    ])->all())
                    ->columns(6)
                    ->schema([
                        TextEntry::make('product')->label(__('sales::orders.item.product')),
                        TextEntry::make('sku')->label(__('sales::orders.item.sku')),
                        TextEntry::make('quantity')->label(__('sales::orders.item.quantity')),
                        TextEntry::make('unit_price')->label(__('sales::orders.item.unit_price')),
                        TextEntry::make('line_total')->label(__('sales::orders.item.line_total')),
                        TextEntry::make('commission')->label(__('sales::orders.item.commission_amount')),
                    ]),
            ]),
            Section::make(__('sales::orders.timeline'))->schema([
                RepeatableEntry::make('timeline')
                    ->hiddenLabel()
                    ->state(fn (Order $record): array => AdminScope::on(OrderStatusLog::class)->where('order_id', $record->id)->orderBy('id')->get()->map(fn (OrderStatusLog $log): array => [
                        'from' => $log->from_status ? __('sales::orders.statuses.'.$log->from_status->value) : '—',
                        'to' => __('sales::orders.statuses.'.$log->to_status->value),
                        'actor' => __('sales::orders.actors.'.$log->actor_type),
                        'note' => $log->note ?? '',
                        'at' => $log->created_at->toDateTimeString(),
                    ])->all())
                    ->columns(5)
                    ->schema([
                        TextEntry::make('at')->label(__('sales::orders.placed_at')),
                        TextEntry::make('from')->label(__('sales::orders.from')),
                        TextEntry::make('to')->label(__('sales::orders.to')),
                        TextEntry::make('actor')->label(__('sales::orders.actor')),
                        TextEntry::make('note')->label(__('sales::orders.note')),
                    ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->label(__('sales::orders.number'))->searchable(),
                TextColumn::make('store.name')->label(__('sales::orders.store')),
                TextColumn::make('customer_snapshot.name')->label(__('sales::orders.customer')),
                TextColumn::make('status')
                    ->label(__('sales::orders.status'))
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => __('sales::orders.statuses.'.$state->value)),
                TextColumn::make('total')->label(__('sales::orders.total'))->formatStateUsing(fn (int $state): string => Money::fromPiasters($state)->format()),
                TextColumn::make('created_at')->label(__('sales::orders.placed_at'))->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('store_id')
                    ->label(__('sales::orders.store'))
                    ->options(fn (): array => Store::query()->orderBy('name')->pluck('name', 'id')->all()),
                SelectFilter::make('status')
                    ->label(__('sales::orders.status'))
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $status): array => [$status->value => __('sales::orders.statuses.'.$status->value)])->all()),
                Filter::make('placed')
                    ->schema([
                        DatePicker::make('from')->label(__('sales::orders.date_from')),
                        DatePicker::make('until')->label(__('sales::orders.date_until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
                self::cancelAction(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }

    public static function cancelAction(): Action
    {
        return Action::make('cancel')
            ->label(__('sales::orders.actions.cancel'))
            ->color('danger')
            ->visible(fn (Order $record): bool => $record->status->holdsStock())
            ->schema([
                Textarea::make('reason')->label(__('sales::orders.actions.reason'))->required()->maxLength(1000),
            ])
            ->action(function (array $data, Order $record): void {
                try {
                    app(CancelOrder::class)($record, 'admin', auth()->id(), $data['reason']);
                } catch (InvalidOrderTransition) {
                    Notification::make()->title(__('sales::orders.actions.cannot_cancel'))->danger()->send();

                    return;
                }

                Notification::make()->title(__('sales::orders.actions.cancelled'))->success()->send();
            });
    }
}
