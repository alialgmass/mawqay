<?php

namespace Modules\Ledger\Filament\Admin\Resources\LedgerEntries;

use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Money\Money;
use Modules\Core\Support\AdminScope;
use Modules\Ledger\Enums\EntryDirection;
use Modules\Ledger\Enums\LedgerAccount;
use Modules\Ledger\Filament\Admin\Resources\LedgerEntries\Pages\ListLedgerEntries;
use Modules\Ledger\Models\LedgerEntry;
use Modules\Tenancy\Models\Store;

/**
 * Every entry in the books, newest first. Read-only: there is nothing to create, edit or delete.
 */
class LedgerEntryResource extends Resource
{
    protected static ?string $model = LedgerEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('panel.admin_nav.finance');
    }

    public static function getModelLabel(): string
    {
        return __('ledger::ledger.entry.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ledger::ledger.entry.plural');
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

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /**
     * @return Builder<LedgerEntry>
     */
    public static function getEloquentQuery(): Builder
    {
        return AdminScope::on(LedgerEntry::class)->with(['store', 'order' => fn ($query) => $query->withoutGlobalScopes()]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label(__('ledger::ledger.entry.date'))->dateTime()->sortable(),
                TextColumn::make('store.name')->label(__('ledger::ledger.entry.store'))->placeholder('—'),
                TextColumn::make('order.number')->label(__('ledger::ledger.entry.order'))->placeholder('—'),
                TextColumn::make('account')
                    ->label(__('ledger::ledger.entry.account'))
                    ->formatStateUsing(fn (LedgerAccount $state): string => __('ledger::ledger.accounts.'.$state->value)),
                TextColumn::make('direction')
                    ->label(__('ledger::ledger.entry.direction'))
                    ->badge()
                    ->color(fn (EntryDirection $state): string => $state === EntryDirection::Credit ? 'success' : 'warning')
                    ->formatStateUsing(fn (EntryDirection $state): string => __('ledger::ledger.directions.'.$state->value)),
                TextColumn::make('amount')
                    ->label(__('ledger::ledger.entry.amount'))
                    ->formatStateUsing(fn (int $state): string => Money::fromPiasters($state)->format()),
                TextColumn::make('posting_group')->label(__('ledger::ledger.entry.group'))->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('store_id')
                    ->label(__('ledger::ledger.entry.store'))
                    ->options(fn (): array => Store::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, $storeId): Builder => $query->where(fn (Builder $query) => $query
                            ->where('store_id', $storeId)
                            ->orWhereIn('order_id', DB::table('orders')->where('store_id', $storeId)->select('id'))),
                    )),
                Filter::make('order')
                    ->label(__('ledger::ledger.entry.order'))
                    ->schema([TextInput::make('number')->label(__('ledger::ledger.entry.order'))])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['number'] ?? null,
                        fn (Builder $query, string $number): Builder => $query->whereIn('order_id', DB::table('orders')->where('number', 'like', "%{$number}%")->select('id')),
                    )),
                SelectFilter::make('account')
                    ->label(__('ledger::ledger.entry.account'))
                    ->options(collect(LedgerAccount::cases())->mapWithKeys(fn (LedgerAccount $account): array => [$account->value => __('ledger::ledger.accounts.'.$account->value)])->all()),
                Filter::make('created')
                    ->schema([
                        DatePicker::make('from')->label(__('ledger::ledger.entry.from')),
                        DatePicker::make('until')->label(__('ledger::ledger.entry.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLedgerEntries::route('/'),
        ];
    }
}
