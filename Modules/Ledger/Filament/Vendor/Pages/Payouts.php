<?php

namespace Modules\Ledger\Filament\Vendor\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Money\Money;
use Modules\Core\Support\PlatformSettings;
use Modules\Ledger\Actions\RequestPayout;
use Modules\Ledger\Actions\SettlePayout;
use Modules\Ledger\Enums\PayoutMethod;
use Modules\Ledger\Enums\PayoutStatus;
use Modules\Ledger\Exceptions\PayoutNotAllowed;
use Modules\Ledger\Models\Payout;
use Modules\Ledger\Models\PayoutDetail;
use Modules\Ledger\Services\BalanceService;
use Modules\Ledger\Support\StoreBalance;

/**
 * A Vendor Owner's payouts: where to be paid, asking for money that is Available, and the history of requests.
 */
class Payouts extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $slug = 'payouts';

    protected string $view = 'ledger::vendor.payouts';

    /**
     * Only the Vendor Owner asks for money or edits where it goes.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isVendorOwner();
    }

    public static function getNavigationLabel(): string
    {
        return __('ledger::payouts.navigation');
    }

    public function getTitle(): string
    {
        return __('ledger::payouts.vendor.title');
    }

    protected function getHeaderActions(): array
    {
        return [$this->detailsAction(), $this->requestAction()];
    }

    public function detailsAction(): Action
    {
        return Action::make('details')
            ->label(__('ledger::payouts.vendor.details'))
            ->color('gray')
            ->fillForm(fn (): array => PayoutDetail::query()->first()?->only(['method', 'destination']) ?? [])
            ->schema([
                Select::make('method')->label(__('ledger::payouts.vendor.method'))->options(PayoutMethod::options())->required(),
                TextInput::make('destination')->label(__('ledger::payouts.vendor.destination'))->helperText(__('ledger::payouts.vendor.destination_help'))->required()->maxLength(255),
            ])
            ->action(function (array $data): void {
                $store = Filament::getTenant();

                PayoutDetail::query()->updateOrCreate(['store_id' => $store->getKey()], ['method' => $data['method'], 'destination' => $data['destination']]);

                Notification::make()->title(__('ledger::payouts.vendor.details_saved'))->success()->send();
            });
    }

    public function requestAction(): Action
    {
        $minimum = Money::fromPiasters(app(PlatformSettings::class)->minPayoutAmount())->format();

        return Action::make('request')
            ->label(__('ledger::payouts.vendor.request'))
            ->schema([
                TextInput::make('amount')
                    ->label(__('ledger::payouts.vendor.amount'))
                    ->helperText(fn (): string => __('ledger::payouts.vendor.amount_help', ['available' => $this->balance()->available()->format(), 'minimum' => $minimum]))
                    ->numeric()->required()->minValue(0.01),
            ])
            ->action(function (array $data): void {
                try {
                    app(RequestPayout::class)(Filament::getTenant(), (int) round(((float) $data['amount']) * 100), auth()->id());
                } catch (PayoutNotAllowed $exception) {
                    Notification::make()->title(__('ledger::payouts.vendor.refused'))->body($exception->message())->danger()->send();

                    return;
                }

                Notification::make()->title(__('ledger::payouts.vendor.requested'))->success()->send();
            });
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Payout::query()->where('store_id', Filament::getTenant()->getKey()))
            ->columns([
                TextColumn::make('requested_at')->label(__('ledger::payouts.columns.requested_at'))->dateTime()->sortable(),
                TextColumn::make('amount')->label(__('ledger::payouts.columns.amount'))->formatStateUsing(fn (int $state): string => Money::fromPiasters($state)->format()),
                TextColumn::make('transfer_method')->label(__('ledger::payouts.columns.method'))->formatStateUsing(fn (PayoutMethod $state): string => $state->label()),
                TextColumn::make('status')->label(__('ledger::payouts.columns.status'))->badge()
                    ->formatStateUsing(fn (PayoutStatus $state): string => $state->label())
                    ->color(fn (PayoutStatus $state): string => $state->color()),
                TextColumn::make('transfer_reference')->label(__('ledger::payouts.columns.reference'))->placeholder('—'),
                TextColumn::make('reject_reason')->label(__('ledger::payouts.columns.reason'))->placeholder('—'),
            ])
            ->recordActions([$this->cancelAction()])
            ->emptyStateHeading(__('ledger::payouts.vendor.empty'))
            ->defaultSort('id', 'desc');
    }

    public function cancelAction(): Action
    {
        return Action::make('cancel')
            ->label(__('ledger::payouts.vendor.cancel'))
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (Payout $record): bool => $record->isOpen())
            ->action(function (Payout $record): void {
                try {
                    app(SettlePayout::class)->cancel($record, auth()->id());
                } catch (PayoutNotAllowed $exception) {
                    Notification::make()->title(__('ledger::payouts.vendor.refused'))->body($exception->message())->danger()->send();

                    return;
                }

                Notification::make()->title(__('ledger::payouts.vendor.cancelled'))->success()->send();
            });
    }

    public function balance(): StoreBalance
    {
        return app(BalanceService::class)->forStore(Filament::getTenant()->getKey());
    }
}
