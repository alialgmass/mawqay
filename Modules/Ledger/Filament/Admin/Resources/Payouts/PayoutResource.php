<?php

namespace Modules\Ledger\Filament\Admin\Resources\Payouts;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Money\Money;
use Modules\Core\Support\AdminScope;
use Modules\Ledger\Actions\SettlePayout;
use Modules\Ledger\Enums\PayoutMethod;
use Modules\Ledger\Enums\PayoutStatus;
use Modules\Ledger\Exceptions\PayoutNotAllowed;
use Modules\Ledger\Filament\Admin\Resources\Payouts\Pages\ListPayouts;
use Modules\Ledger\Filament\Admin\Resources\Payouts\Pages\ViewPayout;
use Modules\Ledger\Models\Payout;
use Modules\Tenancy\Models\Store;

/**
 * The Platform Admin's queue of Payout requests. The transfer details are shown here, decrypted, and
 * nowhere else; an open request is marked paid with its transfer reference or rejected with a reason.
 */
class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function getModelLabel(): string
    {
        return __('ledger::payouts.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ledger::payouts.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $open = AdminScope::on(Payout::class)->where('status', PayoutStatus::Requested->value)->count();

        return $open > 0 ? (string) $open : null;
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
     * @return Builder<Payout>
     */
    public static function getEloquentQuery(): Builder
    {
        return AdminScope::on(Payout::class)->with('store');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextEntry::make('store.name')->label(__('ledger::payouts.columns.store')),
                TextEntry::make('amount')->label(__('ledger::payouts.columns.amount'))->formatStateUsing(fn (int $state): string => Money::fromPiasters($state)->format()),
                TextEntry::make('status')->label(__('ledger::payouts.columns.status'))->badge()
                    ->formatStateUsing(fn (PayoutStatus $state): string => $state->label())
                    ->color(fn (PayoutStatus $state): string => $state->color()),
                TextEntry::make('transfer_method')->label(__('ledger::payouts.columns.method'))->formatStateUsing(fn (PayoutMethod $state): string => $state->label()),
                TextEntry::make('destination')->label(__('ledger::payouts.columns.destination'))->copyable(),
                TextEntry::make('requested_at')->label(__('ledger::payouts.columns.requested_at'))->dateTime(),
                TextEntry::make('transfer_reference')->label(__('ledger::payouts.columns.reference'))->placeholder('—'),
                TextEntry::make('processed_at')->label(__('ledger::payouts.columns.processed_at'))->dateTime()->placeholder('—'),
                TextEntry::make('reject_reason')->label(__('ledger::payouts.columns.reason'))->placeholder('—'),
                ImageEntry::make('proof_path')->label(__('ledger::payouts.columns.proof'))->disk(config('filesystems.default'))->visibility('private')->visible(fn (Payout $record): bool => $record->proof_path !== null),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('store.name')->label(__('ledger::payouts.columns.store'))->searchable(),
                TextColumn::make('amount')->label(__('ledger::payouts.columns.amount'))->formatStateUsing(fn (int $state): string => Money::fromPiasters($state)->format())->sortable(),
                TextColumn::make('transfer_method')->label(__('ledger::payouts.columns.method'))->formatStateUsing(fn (PayoutMethod $state): string => $state->label()),
                TextColumn::make('status')->label(__('ledger::payouts.columns.status'))->badge()
                    ->formatStateUsing(fn (PayoutStatus $state): string => $state->label())
                    ->color(fn (PayoutStatus $state): string => $state->color()),
                TextColumn::make('requested_at')->label(__('ledger::payouts.columns.requested_at'))->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('ledger::payouts.columns.status'))
                    ->options(collect(PayoutStatus::cases())->mapWithKeys(fn (PayoutStatus $status): array => [$status->value => $status->label()])->all())
                    ->default(PayoutStatus::Requested->value),
                SelectFilter::make('store_id')->label(__('ledger::payouts.columns.store'))
                    ->options(fn (): array => Store::query()->orderBy('name')->pluck('name', 'id')->all()),
            ])
            ->recordActions([ViewAction::make(), ...self::settleActions()])
            ->defaultSort('requested_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayouts::route('/'),
            'view' => ViewPayout::route('/{record}'),
        ];
    }

    /**
     * @return list<Action>
     */
    public static function settleActions(): array
    {
        return [self::markPaidAction(), self::rejectAction()];
    }

    public static function markPaidAction(): Action
    {
        return Action::make('markPaid')
            ->label(__('ledger::payouts.admin.mark_paid'))
            ->color('success')
            ->visible(fn (Payout $record): bool => $record->isOpen())
            ->schema([
                TextInput::make('transfer_reference')->label(__('ledger::payouts.admin.transfer_reference'))->required()->maxLength(255),
                FileUpload::make('proof')->label(__('ledger::payouts.admin.proof'))->image()->disk(config('filesystems.default'))->directory('payout-proofs')->visibility('private')->maxSize(5120),
            ])
            ->action(function (array $data, Payout $record): void {
                self::settle(fn () => app(SettlePayout::class)->markPaid($record, $data['transfer_reference'], $data['proof'] ?? null, auth()->id()), 'paid_done');
            });
    }

    public static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label(__('ledger::payouts.admin.reject'))
            ->color('danger')
            ->visible(fn (Payout $record): bool => $record->isOpen())
            ->schema([
                Textarea::make('reason')->label(__('ledger::payouts.admin.reject_reason'))->required()->maxLength(1000),
            ])
            ->action(function (array $data, Payout $record): void {
                self::settle(fn () => app(SettlePayout::class)->reject($record, $data['reason'], auth()->id()), 'rejected_done');
            });
    }

    private static function settle(callable $step, string $done): void
    {
        try {
            $step();
        } catch (PayoutNotAllowed $exception) {
            Notification::make()->title(__('ledger::payouts.admin.refused'))->body($exception->message())->danger()->send();

            return;
        }

        Notification::make()->title(__('ledger::payouts.admin.'.$done))->success()->send();
    }
}
