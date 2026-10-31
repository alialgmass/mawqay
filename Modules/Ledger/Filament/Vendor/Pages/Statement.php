<?php

namespace Modules\Ledger\Filament\Vendor\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Money\Money;
use Modules\Ledger\Models\LedgerEntry;
use Modules\Ledger\Services\CsvExports;
use Modules\Ledger\Support\EntryDescription;

/**
 * A Vendor Owner statement: only their own Store entries, with what each changed and the running total owed.
 */
class Statement extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('panel.nav.finance');
    }

    protected static ?string $slug = 'statement';

    protected string $view = 'ledger::vendor.statement';

    public static function getNavigationLabel(): string
    {
        return __('ledger::ledger.statement.navigation');
    }

    public function getTitle(): string
    {
        return __('ledger::ledger.statement.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label(__('ledger::reports.export'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->schema([
                    DatePicker::make('from')->label(__('ledger::ledger.statement.from')),
                    DatePicker::make('until')->label(__('ledger::ledger.statement.until')),
                ])
                ->action(fn (array $data) => app(CsvExports::class)->statement(Filament::getTenant()->getKey(), $data['from'] ?? null, $data['until'] ?? null)),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => LedgerEntry::query()
                ->withRunningBalance()
                ->where('ledger_entries.store_id', Filament::getTenant()->getKey())
                ->vendorAccounts()
                ->with('order'))
            ->columns([
                TextColumn::make('created_at')->label(__('ledger::ledger.statement.date'))->dateTime()->sortable(),
                TextColumn::make('order.number')->label(__('ledger::ledger.statement.order'))->placeholder('—'),
                TextColumn::make('description')
                    ->label(__('ledger::ledger.statement.description'))
                    ->state(fn (LedgerEntry $record): string => EntryDescription::for($record)),
                TextColumn::make('amount')
                    ->label(__('ledger::ledger.statement.amount'))
                    ->state(fn (LedgerEntry $record): string => Money::fromPiasters($record->signedAmount())->format())
                    ->color(fn (LedgerEntry $record): string => $record->signedAmount() < 0 ? 'danger' : 'success'),
                TextColumn::make('running_balance')
                    ->label(__('ledger::ledger.statement.running_balance'))
                    ->state(fn (LedgerEntry $record): string => Money::fromPiasters((int) $record->running_balance)->format()),
            ])
            ->filters([
                Filter::make('period')
                    ->schema([
                        DatePicker::make('from')->label(__('ledger::ledger.statement.from')),
                        DatePicker::make('until')->label(__('ledger::ledger.statement.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('ledger_entries.created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('ledger_entries.created_at', '<=', $date))),
            ])
            ->emptyStateHeading(__('ledger::ledger.statement.empty'))
            ->defaultSort('ledger_entries.id', 'desc');
    }
}
