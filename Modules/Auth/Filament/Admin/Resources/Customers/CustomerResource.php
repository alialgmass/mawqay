<?php

namespace Modules\Auth\Filament\Admin\Resources\Customers;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Auth\Actions\SetCustomerActive;
use Modules\Auth\Filament\Admin\Resources\Customers\Pages\ListCustomers;
use Modules\Auth\Filament\Admin\Resources\Customers\Pages\ViewCustomer;
use Modules\Auth\Filament\Admin\Resources\Customers\RelationManagers\AddressesRelationManager;
use Modules\Auth\Models\Customer;
use Modules\Auth\Support\PhoneNumber;
use Modules\Core\Support\AdminScope;
use Modules\Tenancy\Models\Store;

/**
 * The Platform Admin's read-only view of Customers across Stores. The only
 * thing an Admin can do to a Customer is switch the account on or off.
 */
class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('panel.admin_nav.sales');
    }

    public static function getModelLabel(): string
    {
        return __('auth::admin.customer');
    }

    public static function getPluralModelLabel(): string
    {
        return __('auth::admin.customers');
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
     * @return Builder<Customer>
     */
    public static function getEloquentQuery(): Builder
    {
        return AdminScope::on(Customer::class)->with('store');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('store.name')->label(__('auth::admin.store')),
            TextEntry::make('name')->label(__('auth::admin.name')),
            TextEntry::make('email')->label(__('auth::admin.email')),
            TextEntry::make('phone')->label(__('auth::admin.phone')),
            IconEntry::make('is_active')->label(__('auth::admin.is_active'))->boolean(),
            TextEntry::make('created_at')->label(__('auth::admin.registered'))->dateTime(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('auth::admin.name'))->searchable(),
                TextColumn::make('store.name')->label(__('auth::admin.store')),
                TextColumn::make('email')->label(__('auth::admin.email'))->searchable(),
                TextColumn::make('phone')
                    ->label(__('auth::admin.phone'))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(
                        fn (Builder $inner) => $inner->where('phone', 'like', "%{$search}%")->when(
                            PhoneNumber::normalize($search),
                            fn (Builder $q, string $normalized) => $q->orWhere('phone', $normalized),
                        ),
                    )),
                IconColumn::make('is_active')->label(__('auth::admin.is_active'))->boolean(),
                TextColumn::make('created_at')->label(__('auth::admin.registered'))->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('store_id')
                    ->label(__('auth::admin.store'))
                    ->options(fn (): array => Store::query()->orderBy('name')->pluck('name', 'id')->all()),
                SelectFilter::make('is_active')
                    ->label(__('auth::admin.is_active'))
                    ->options([1 => __('auth::admin.active'), 0 => __('auth::admin.inactive')]),
            ])
            ->recordActions([
                ViewAction::make(),
                self::activeAction('activate', true),
                self::activeAction('deactivate', false),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [AddressesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'view' => ViewCustomer::route('/{record}'),
        ];
    }

    public static function activeAction(string $name, bool $active): Action
    {
        return Action::make($name)
            ->label(__("auth::admin.actions.{$name}"))
            ->requiresConfirmation()
            ->visible(fn (Customer $record): bool => $record->is_active !== $active)
            ->action(function (Customer $record) use ($active): void {
                app(SetCustomerActive::class)($record, $active);

                Notification::make()->title(__('auth::admin.actions.updated'))->success()->send();
            });
    }
}
