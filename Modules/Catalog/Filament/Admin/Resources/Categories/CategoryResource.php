<?php

namespace Modules\Catalog\Filament\Admin\Resources\Categories;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Catalog\Filament\Admin\Resources\Categories\Pages\ListCategories;
use Modules\Catalog\Models\Category;
use Modules\Core\Scopes\StoreScope;
use Modules\Core\Support\AdminScope;
use Modules\Tenancy\Models\Store;

/**
 * Read-only across Stores: the Platform Admin looks, the Vendor edits.
 */
class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('panel.admin_nav.stores');
    }

    public static function getModelLabel(): string
    {
        return __('catalog::categories.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('catalog::categories.plural');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * @return Builder<Category>
     */
    public static function getEloquentQuery(): Builder
    {
        return AdminScope::on(Category::class)->with([
            'store',
            'parent' => fn ($parent) => $parent->withoutGlobalScope(StoreScope::class),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('catalog::categories.name'))
                    ->formatStateUsing(fn (string $state, Category $record): string => $record->parent ? "{$record->parent->name} / {$state}" : $state)
                    ->searchable(),
                TextColumn::make('store.name')->label(__('catalog::categories.store'))->searchable(),
                IconColumn::make('is_active')->label(__('catalog::categories.is_active'))->boolean(),
            ])
            ->filters([
                SelectFilter::make('store_id')
                    ->label(__('catalog::categories.store'))
                    ->options(fn (): array => Store::query()->orderBy('name')->pluck('name', 'id')->all()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
        ];
    }
}
