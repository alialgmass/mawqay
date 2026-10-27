<?php

namespace Modules\Sales\Filament\Admin\Resources\Orders\Support;

use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Modules\Core\Support\AdminScope;
use Modules\Core\Support\StoreContext;
use Modules\Sales\Actions\ForceOrderStatus;
use Modules\Sales\Actions\RefundOrder;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Enums\RefundReason;
use Modules\Sales\Exceptions\InvalidOrderTransition;
use Modules\Sales\Exceptions\RefundNotAllowed;
use Modules\Sales\Models\Order;
use Modules\Sales\Models\OrderItem;
use Modules\Sales\Models\Refund;

/**
 * The Platform Admin's money and repair actions on an Order: refund it, or force its status with a reason.
 */
class AdminOrderActions
{
    /**
     * @return list<Action>
     */
    public static function all(): array
    {
        return [self::refund(), self::forceStatus()];
    }

    public static function refund(): Action
    {
        return Action::make('refund')
            ->label(__('sales::orders.refund.action'))
            ->color('danger')
            ->visible(fn (Order $record): bool => in_array($record->status, [OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered, OrderStatus::Completed], true))
            ->schema(fn (Order $record): array => [
                Hidden::make('reference')->default(fn (): string => (string) Str::uuid()),
                TextInput::make('amount')
                    ->label(__('sales::orders.refund.amount'))
                    ->helperText(__('sales::orders.refund.amount_help', ['left' => self::left($record)]))
                    ->numeric()->minValue(0.01),
                Select::make('reason')->label(__('sales::orders.refund.reason'))->options(RefundReason::options())->required(),
                Textarea::make('note')->label(__('sales::orders.note'))->maxLength(1000),
                Toggle::make('restock')->label(__('sales::orders.refund.restock')),
                ...self::items($record)->map(fn (OrderItem $item) => TextInput::make('restock_'.$item->id)
                    ->label($item->product_name.' ('.$item->size.' / '.$item->color.')')
                    ->helperText(__('sales::orders.refund.restock_quantity_help'))
                    ->numeric()->minValue(0)->maxValue($item->quantity)->default(0))->all(),
                Textarea::make('override_reason')
                    ->label(__('sales::orders.refund.override_reason'))
                    ->visible($record->status === OrderStatus::Completed)
                    ->required($record->status === OrderStatus::Completed),
            ])
            ->action(function (array $data, Order $record): void {
                abort_unless(Gate::allows('refund', $record), 403);

                $quantities = [];

                foreach (self::items($record) as $item) {
                    $quantities[$item->id] = (int) ($data['restock_'.$item->id] ?? 0);
                }

                try {
                    app(StoreContext::class)->runAs($record->store_id, fn () => app(RefundOrder::class)(
                        $record,
                        filled($data['amount'] ?? null) ? (int) round(((float) $data['amount']) * 100) : null,
                        RefundReason::from($data['reason']),
                        $data['note'] ?? null,
                        auth()->id(),
                        $data['reference'],
                        (bool) ($data['restock'] ?? false),
                        array_filter($quantities),
                        $data['override_reason'] ?? null,
                    ));
                } catch (RefundNotAllowed $exception) {
                    Notification::make()->title(__('sales::orders.refund.refused'))->body($exception->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title(__('sales::orders.refund.done'))->success()->send();
            });
    }

    public static function forceStatus(): Action
    {
        return Action::make('forceStatus')
            ->label(__('sales::orders.force.action'))
            ->color('warning')
            ->visible(fn (Order $record): bool => $record->status->allowedNext() !== [])
            ->schema(fn (Order $record): array => [
                Select::make('status')
                    ->label(__('sales::orders.status'))
                    ->options(collect($record->status->allowedNext())->reject(fn (OrderStatus $status): bool => in_array($status, [OrderStatus::Paid, OrderStatus::Refunded, OrderStatus::Completed], true))
                        ->mapWithKeys(fn (OrderStatus $status): array => [$status->value => __('sales::orders.statuses.'.$status->value)])->all())
                    ->required(),
                Textarea::make('reason')->label(__('sales::orders.force.reason'))->required()->maxLength(1000),
            ])
            ->action(function (array $data, Order $record): void {
                abort_unless(Gate::allows('refund', $record), 403);

                try {
                    app(StoreContext::class)->runAs($record->store_id, fn () => app(ForceOrderStatus::class)($record, OrderStatus::from($data['status']), $data['reason'], auth()->id()));
                } catch (InvalidOrderTransition|RefundNotAllowed) {
                    Notification::make()->title(__('sales::orders.force.refused'))->danger()->send();

                    return;
                }

                Notification::make()->title(__('sales::orders.force.done'))->success()->send();
            });
    }

    /**
     * @return Collection<int, OrderItem>
     */
    private static function items(Order $order): Collection
    {
        return AdminScope::on(OrderItem::class)->where('order_id', $order->id)->orderBy('id')->get();
    }

    private static function left(Order $order): string
    {
        $refunded = (int) AdminScope::on(Refund::class)->where('order_id', $order->id)->sum('amount');

        return number_format(($order->total - $refunded) / 100, 2, '.', '');
    }
}
