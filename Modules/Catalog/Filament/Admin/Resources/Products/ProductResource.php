<?php

namespace Modules\Catalog\Filament\Admin\Resources\Products;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Actions\HideProduct;
use Modules\Catalog\Actions\RestoreProduct;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Filament\Admin\Resources\Products\Pages\ListProducts;
use Modules\Catalog\Filament\Vendor\Resources\Products\ProductResource as VendorProductResource;
use Modules\Catalog\Models\Product;
use Modules\Core\Scopes\StoreScope;
use Modules\Core\Support\AdminScope;
use Modules\Tenancy\Models\Store;

/**
 * The Platform Admin's read-mostly view of every Store's Products: find,
 * hide with a reason, restore. Editing a Vendor's content is not offered.
 */
class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    public static function getModelLabel(): string
    {
        return __('catalog::products.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('catalog::products.plural');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * @return Builder<Product>
     */
    public static function getEloquentQuery(): Builder
    {
        return AdminScope::on(Product::class)->with([
            'store',
            'category' => fn ($category) => $category->withoutGlobalScope(StoreScope::class),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('catalog::products.name'))->searchable(),
                TextColumn::make('store.name')->label(__('catalog::products.store'))->searchable(),
                TextColumn::make('category.name')->label(__('catalog::products.category')),
                TextColumn::make('status')
                    ->label(__('catalog::products.status'))
                    ->badge()
                    ->color(fn (ProductStatus $state): string => match ($state) {
                        ProductStatus::Draft => 'gray',
                        ProductStatus::Published => 'success',
                        ProductStatus::HiddenByAdmin => 'danger',
                    })
                    ->formatStateUsing(fn (ProductStatus $state): string => __('catalog::products.statuses.'.$state->value)),
                TextColumn::make('hide_reason')->label(__('catalog::products.hide_reason'))->limit(40),
            ])
            ->filters([
                SelectFilter::make('store_id')
                    ->label(__('catalog::products.store'))
                    ->options(fn (): array => Store::query()->orderBy('name')->pluck('name', 'id')->all()),
                SelectFilter::make('status')
                    ->label(__('catalog::products.status'))
                    ->options(VendorProductResource::statusOptions()),
            ])
            ->recordActions([
                self::hideAction(),
                self::restoreAction(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
        ];
    }

    private static function hideAction(): Action
    {
        return Action::make('hide')
            ->label(__('catalog::products.actions.hide'))
            ->color('danger')
            ->visible(fn (Product $record): bool => $record->status !== ProductStatus::HiddenByAdmin)
            ->schema([
                Textarea::make('reason')->label(__('catalog::products.hide_reason'))->required()->maxLength(1000),
            ])
            ->action(function (array $data, Product $record): void {
                try {
                    app(HideProduct::class)($record, $data['reason']);
                } catch (ValidationException $exception) {
                    Notification::make()->title(collect($exception->errors())->flatten()->first())->danger()->send();

                    return;
                }

                Notification::make()->title(__('catalog::products.actions.hidden'))->success()->send();
            });
    }

    private static function restoreAction(): Action
    {
        return Action::make('restore')
            ->label(__('catalog::products.actions.restore'))
            ->requiresConfirmation()
            ->visible(fn (Product $record): bool => $record->status === ProductStatus::HiddenByAdmin)
            ->action(function (Product $record): void {
                app(RestoreProduct::class)($record);

                Notification::make()->title(__('catalog::products.actions.restored'))->success()->send();
            });
    }
}
