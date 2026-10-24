<?php

namespace Modules\Auth\Filament\Admin\Resources\Customers\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\Address;
use Modules\Core\Support\AdminScope;

/**
 * The Customer's addresses, read-only.
 */
class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('auth::admin.addresses');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => AdminScope::on(Address::class)->where('customer_id', $this->getOwnerRecord()->getKey()))
            ->columns([
                TextColumn::make('label')->label(__('auth::admin.label')),
                TextColumn::make('recipient_name')->label(__('auth::admin.recipient')),
                TextColumn::make('phone')->label(__('auth::admin.phone')),
                TextColumn::make('governorate')
                    ->label(__('auth::admin.governorate'))
                    ->formatStateUsing(fn (string $state): string => config("customers.governorates.{$state}", $state)),
                TextColumn::make('city')->label(__('auth::admin.city')),
                TextColumn::make('address_line')->label(__('auth::admin.address')),
                IconColumn::make('is_default')->label(__('auth::admin.default'))->boolean(),
            ])
            ->paginated(false);
    }
}
