<?php

namespace Modules\Sales\Filament\Vendor\Resources\Orders\Support;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;
use Modules\Sales\Contracts\Fulfilment;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Exceptions\InvalidOrderTransition;
use Modules\Sales\Models\Order;

/**
 * The Vendor's three fulfilment steps. Each is offered only from the right status, and is checked
 * again when pressed so an Order that moved since the page loaded is refused.
 */
class FulfilmentActions
{
    /**
     * @return list<Action>
     */
    public static function all(): array
    {
        return [self::startProcessing(), self::markShipped(), self::markDelivered()];
    }

    public static function startProcessing(): Action
    {
        return Action::make('startProcessing')
            ->label(__('sales::orders.fulfilment.start_processing'))
            ->color('primary')
            ->visible(fn (Order $record): bool => $record->status === OrderStatus::Paid)
            ->requiresConfirmation()
            ->action(fn (Order $record) => self::run($record, fn (Fulfilment $fulfilment) => $fulfilment->startProcessing($record, 'vendor', auth()->id()), 'processing_started'));
    }

    public static function markShipped(): Action
    {
        return Action::make('markShipped')
            ->label(__('sales::orders.fulfilment.mark_shipped'))
            ->color('primary')
            ->visible(fn (Order $record): bool => $record->status === OrderStatus::Processing)
            ->schema([
                TextInput::make('courier_name')->label(__('sales::orders.fulfilment.courier_name'))->required()->maxLength(255),
                TextInput::make('tracking_number')->label(__('sales::orders.fulfilment.tracking_number'))->maxLength(255),
                TextInput::make('tracking_url')->label(__('sales::orders.fulfilment.tracking_url'))->url()->maxLength(2048),
                Textarea::make('note')->label(__('sales::orders.fulfilment.note'))->maxLength(1000),
            ])
            ->action(fn (array $data, Order $record) => self::run($record, fn (Fulfilment $fulfilment) => $fulfilment->markShipped($record, $data, 'vendor', auth()->id()), 'shipped'));
    }

    public static function markDelivered(): Action
    {
        return Action::make('markDelivered')
            ->label(__('sales::orders.fulfilment.mark_delivered'))
            ->color('success')
            ->visible(fn (Order $record): bool => $record->status === OrderStatus::Shipped)
            ->requiresConfirmation()
            ->action(fn (Order $record) => self::run($record, fn (Fulfilment $fulfilment) => $fulfilment->markDelivered($record, 'vendor', auth()->id()), 'delivered'));
    }

    private static function run(Order $order, callable $step, string $done): void
    {
        abort_unless(Gate::allows('fulfil', $order), 403);

        try {
            $step(app(Fulfilment::class));
        } catch (InvalidOrderTransition) {
            Notification::make()->title(__('sales::orders.fulfilment.moved'))->danger()->send();

            return;
        }

        Notification::make()->title(__('sales::orders.fulfilment.'.$done))->success()->send();
    }
}
