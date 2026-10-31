<?php

namespace Modules\Payments\Filament\Admin\Resources\Payments;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Money\Money;
use Modules\Core\Support\AdminScope;
use Modules\Payments\Actions\ApprovePayment;
use Modules\Payments\Actions\RejectPayment;
use Modules\Payments\Enums\PaymentStatus;
use Modules\Payments\Exceptions\PaymentCannotBeReviewed;
use Modules\Payments\Filament\Admin\Resources\Payments\Pages\ListPayments;
use Modules\Payments\Models\Payment;
use Modules\Payments\Support\PaymentLinks;
use Modules\Sales\Enums\OrderStatus;

/**
 * The Platform Admin's queue of Receipts: oldest first, defaulting to those
 * waiting for a decision. Receipts are decided here, never edited or deleted.
 */
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('panel.admin_nav.sales');
    }

    public static function getModelLabel(): string
    {
        return __('payments::review.payment.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('payments::review.payment.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $waiting = self::query()->where('status', PaymentStatus::PendingReview->value)->count();

        return $waiting > 0 ? (string) $waiting : null;
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
     * @return Builder<Payment>
     */
    public static function getEloquentQuery(): Builder
    {
        return self::query()->with(['store', 'order' => fn ($query) => $query->withoutGlobalScopes()]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.number')->label(__('payments::review.payment.order'))->searchable(),
                TextColumn::make('store.name')->label(__('payments::review.payment.store')),
                TextColumn::make('order.customer_snapshot.name')->label(__('payments::review.payment.customer')),
                TextColumn::make('method')
                    ->label(__('payments::review.payment.method'))
                    ->formatStateUsing(fn (string $state): string => __('sales::orders.payment_methods.'.$state)),
                TextColumn::make('order.total')
                    ->label(__('payments::review.payment.order_total'))
                    ->formatStateUsing(fn (int $state): string => Money::fromPiasters($state)->format()),
                TextColumn::make('amount')
                    ->label(__('payments::review.payment.expected'))
                    ->formatStateUsing(fn (int $state): string => Money::fromPiasters($state)->format()),
                TextColumn::make('transfer_reference')->label(__('payments::review.payment.transfer_reference'))->placeholder('—')->searchable(),
                TextColumn::make('sender_phone')->label(__('payments::review.payment.sender_phone'))->placeholder('—'),
                TextColumn::make('attempt')->label(__('payments::review.payment.attempt')),
                TextColumn::make('duplicate_of_id')
                    ->label(__('payments::review.payment.duplicate'))
                    ->badge()
                    ->color('danger')
                    ->state(fn (Payment $record): ?string => $record->isDuplicate() ? __('payments::review.payment.duplicate') : null)
                    ->tooltip(__('payments::review.payment.duplicate_hint')),
                TextColumn::make('status')
                    ->label(__('payments::review.payment.status'))
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state): string => __('payments::review.payment.statuses.'.$state->value)),
                TextColumn::make('created_at')->label(__('payments::review.payment.submitted_at'))->dateTime()->sortable(),
                TextColumn::make('rejection_reason')->label(__('payments::review.payment.rejection_reason'))->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('payments::review.payment.status'))
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(fn (PaymentStatus $status): array => [$status->value => __('payments::review.payment.statuses.'.$status->value)])->all())
                    ->default(PaymentStatus::PendingReview->value),
            ])
            ->recordActions([
                self::previewAction(),
                self::approveAction(),
                self::rejectAction(),
            ])
            ->defaultSort('created_at', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
        ];
    }

    public static function previewAction(): Action
    {
        return Action::make('preview')
            ->label(__('payments::review.actions.preview'))
            ->icon(Heroicon::OutlinedEye)
            ->modalHeading(fn (Payment $record): string => $record->order->number)
            ->modalContent(fn (Payment $record) => view('payments::receipt-preview', ['url' => PaymentLinks::receipt($record), 'mime' => $record->receipt_mime]))
            ->modalSubmitAction(false)
            ->modalWidth('4xl');
    }

    public static function approveAction(): Action
    {
        return Action::make('approve')
            ->label(__('payments::review.actions.approve'))
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription(__('payments::review.actions.approve_confirm'))
            ->visible(fn (Payment $record): bool => self::canBeDecided($record))
            ->action(function (Payment $record): void {
                try {
                    app(ApprovePayment::class)($record, (int) auth()->id());
                } catch (PaymentCannotBeReviewed $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title(__('payments::review.actions.approved'))->success()->send();
            });
    }

    public static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label(__('payments::review.actions.reject'))
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (Payment $record): bool => self::canBeDecided($record))
            ->schema([
                Textarea::make('reason')->label(__('payments::review.actions.reason'))->required()->maxLength(1000),
            ])
            ->action(function (array $data, Payment $record): void {
                try {
                    app(RejectPayment::class)($record, (int) auth()->id(), $data['reason']);
                } catch (PaymentCannotBeReviewed $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title(__('payments::review.actions.rejected'))->success()->send();
            });
    }

    /**
     * A Receipt can be decided while it waits and its Order is still in review;
     * a cancelled Order leaves nothing to approve or reject.
     */
    private static function canBeDecided(Payment $payment): bool
    {
        return $payment->status === PaymentStatus::PendingReview && $payment->order?->status === OrderStatus::PaymentReview;
    }

    /**
     * @return Builder<Payment>
     */
    private static function query(): Builder
    {
        return AdminScope::on(Payment::class);
    }
}
