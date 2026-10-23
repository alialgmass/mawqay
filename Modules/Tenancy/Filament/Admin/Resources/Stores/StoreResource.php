<?php

namespace Modules\Tenancy\Filament\Admin\Resources\Stores;

use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Support\AdminScope;
use Modules\Tenancy\Enums\StoreStatus;
use Modules\Tenancy\Filament\Admin\Resources\Stores\Pages\ListStores;
use Modules\Tenancy\Filament\Admin\Resources\Stores\Pages\ViewStore;
use Modules\Tenancy\Models\Store;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    public static function getModelLabel(): string
    {
        return __('tenancy::stores.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('tenancy::stores.plural');
    }

    /**
     * The Admin Panel sees every Store.
     *
     * @return Builder<Store>
     */
    public static function getEloquentQuery(): Builder
    {
        return AdminScope::on(Store::class);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')->label(__('tenancy::stores.name')),
            TextEntry::make('slug')->label(__('tenancy::stores.slug')),
            TextEntry::make('status')
                ->label(__('tenancy::stores.status'))
                ->badge()
                ->formatStateUsing(fn (StoreStatus $state): string => __('tenancy::stores.statuses.'.$state->value)),
            TextEntry::make('contact_email')->label(__('tenancy::stores.contact_email')),
            TextEntry::make('contact_phone')->label(__('tenancy::stores.contact_phone')),
            TextEntry::make('created_at')->label(__('tenancy::stores.created_at'))->dateTime(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('tenancy::stores.name'))->searchable(),
                TextColumn::make('slug')->label(__('tenancy::stores.slug'))->searchable(),
                TextColumn::make('status')
                    ->label(__('tenancy::stores.status'))
                    ->badge()
                    ->formatStateUsing(fn (StoreStatus $state): string => __('tenancy::stores.statuses.'.$state->value)),
                TextColumn::make('created_at')->label(__('tenancy::stores.created_at'))->dateTime()->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStores::route('/'),
            'view' => ViewStore::route('/{record}'),
        ];
    }
}
