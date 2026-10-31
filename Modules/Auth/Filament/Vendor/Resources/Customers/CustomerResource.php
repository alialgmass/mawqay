<?php

namespace Modules\Auth\Filament\Vendor\Resources\Customers;

use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Auth\Filament\Vendor\Resources\Customers\Pages\ListCustomers;
use Modules\Auth\Filament\Vendor\Resources\Customers\Pages\ViewCustomer;
use Modules\Auth\Models\Customer;

/**
 * A Vendor Owner's read-only list of their own Store's Customers: name,
 * city and registration date only. No email, no phone, no export: contact
 * details are shared per Order, and only while it needs fulfilling.
 */
class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('panel.nav.sales');
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
        return parent::getEloquentQuery()->with('defaultAddress');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')->label(__('auth::admin.name')),
            TextEntry::make('defaultAddress.city')->label(__('auth::admin.city')),
            TextEntry::make('created_at')->label(__('auth::admin.registered'))->dateTime(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('auth::admin.name'))->searchable(),
                TextColumn::make('defaultAddress.city')->label(__('auth::admin.city')),
                TextColumn::make('created_at')->label(__('auth::admin.registered'))->dateTime()->sortable(),
            ])
            ->recordActions([ViewAction::make()])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'view' => ViewCustomer::route('/{record}'),
        ];
    }
}
