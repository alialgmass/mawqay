<?php

namespace Modules\Ledger\Filament\Admin\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Money\Money;
use Modules\Ledger\Filament\Admin\Resources\LedgerEntries\LedgerEntryResource;
use Modules\Ledger\Services\BalanceService;
use Modules\Tenancy\Models\Store;

/**
 * What the Platform owes each Store, with the Platform totals above and a way into each Store entries.
 */
class VendorBalances extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?string $slug = 'vendor-balances';

    protected string $view = 'ledger::admin.vendor-balances';

    public static function getNavigationLabel(): string
    {
        return __('ledger::ledger.balances.navigation');
    }

    public function getTitle(): string
    {
        return __('ledger::ledger.balances.title');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isPlatformAdmin();
    }

    /**
     * @return array<string, string>
     */
    public function totals(): array
    {
        $totals = app(BalanceService::class)->platformTotals();

        return [
            __('ledger::ledger.balances.cash') => Money::fromPiasters($totals['cash'])->format(),
            __('ledger::ledger.balances.payables') => Money::fromPiasters($totals['payables'])->format(),
            __('ledger::ledger.balances.commission') => Money::fromPiasters($totals['commission_revenue'])->format(),
        ];
    }

    public function table(Table $table): Table
    {
        $balance = fn (Store $store) => app(BalanceService::class)->forStore($store->id);

        return $table
            ->query(fn () => Store::query())
            ->columns([
                TextColumn::make('name')->label(__('ledger::ledger.balances.store'))->searchable()->sortable(),
                TextColumn::make('pending')->label(__('ledger::ledger.balances.pending'))->state(fn (Store $record): string => $balance($record)->pending()->format()),
                TextColumn::make('available')->label(__('ledger::ledger.balances.available'))->state(fn (Store $record): string => $balance($record)->available()->format()),
                TextColumn::make('payout_pending')->label(__('ledger::ledger.balances.payout_pending'))->state(fn (Store $record): string => $balance($record)->payoutPending()->format()),
                TextColumn::make('paid')->label(__('ledger::ledger.balances.paid'))->state(fn (Store $record): string => $balance($record)->paid()->format()),
            ])
            ->recordActions([
                Action::make('entries')
                    ->label(__('ledger::ledger.balances.view_entries'))
                    ->icon(Heroicon::OutlinedBookOpen)
                    ->url(fn (Store $record): string => LedgerEntryResource::getUrl('index', ['filters' => ['store_id' => ['value' => $record->id]]])),
            ])
            ->defaultSort('name');
    }
}
