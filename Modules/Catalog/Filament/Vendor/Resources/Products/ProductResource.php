<?php

namespace Modules\Catalog\Filament\Vendor\Resources\Products;

use BackedEnum;
use Closure;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Actions\PublishProduct;
use Modules\Catalog\Actions\UnpublishProduct;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Filament\Vendor\Resources\Products\Pages\CreateProduct;
use Modules\Catalog\Filament\Vendor\Resources\Products\Pages\EditProduct;
use Modules\Catalog\Filament\Vendor\Resources\Products\Pages\ListProducts;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductMedia;

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

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')
                    ->label(__('catalog::products.name'))
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                TextInput::make('slug')
                    ->label(__('catalog::products.slug'))
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('store_id', Filament::getTenant()?->getKey()),
                    ),
                Select::make('category_id')
                    ->label(__('catalog::products.category'))
                    ->relationship('category', 'name')
                    ->required()
                    ->preload()
                    ->searchable(),
                TextInput::make('hide_reason')
                    ->label(__('catalog::products.hide_reason'))
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (?Product $record): bool => $record?->status === ProductStatus::HiddenByAdmin),
                self::moneyField('base_price')->required(),
                self::moneyField('sale_price')->lt('base_price'),
                RichEditor::make('description')
                    ->label(__('catalog::products.description'))
                    ->columnSpanFull(),
            ]),
            Section::make(__('catalog::products.variants'))->schema([
                Repeater::make('variants')
                    ->label(__('catalog::products.variants'))
                    ->relationship()
                    ->columns(5)
                    ->schema([
                        TextInput::make('sku')
                            ->label(__('catalog::products.variant.sku'))
                            ->required()
                            ->maxLength(100)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('store_id', Filament::getTenant()?->getKey()),
                            ),
                        TextInput::make('size')->label(__('catalog::products.variant.size'))->required()->maxLength(50),
                        TextInput::make('color')->label(__('catalog::products.variant.color'))->required()->maxLength(50),
                        self::moneyField('price_override')->label(__('catalog::products.variant.price_override')),
                        TextInput::make('stock')
                            ->label(__('catalog::products.variant.stock'))
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                    ])
                    ->rule(fn (): Closure => self::variantsAreDistinct()),
            ]),
            Section::make(__('catalog::products.images'))->schema([
                Repeater::make('media')
                    ->label(__('catalog::products.images'))
                    ->relationship()
                    ->orderColumn('sort')
                    ->maxItems(ProductMedia::MAX_PER_PRODUCT)
                    ->grid(4)
                    ->schema([
                        FileUpload::make('path')
                            ->label(__('catalog::products.image'))
                            ->image()
                            ->maxSize(5120)
                            ->disk('public')
                            ->directory(fn (): string => 'stores/'.Filament::getTenant()?->getKey().'/products')
                            ->visibility('public')
                            ->required(),
                    ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['media'])->withSum('variants', 'stock'))
            ->columns([
                ImageColumn::make('cover')
                    ->label(__('catalog::products.cover'))
                    ->state(fn (Product $record): ?string => $record->media->first()?->url('thumb')),
                TextColumn::make('name')->label(__('catalog::products.name'))->searchable(),
                TextColumn::make('variants_sum_stock')
                    ->label(__('catalog::products.total_stock'))
                    ->default(0)
                    ->badge()
                    ->color(fn (int $state): string => $state <= Product::LOW_STOCK_THRESHOLD ? 'danger' : 'success')
                    ->description(fn (int $state): ?string => $state <= Product::LOW_STOCK_THRESHOLD ? __('catalog::products.low_stock') : null),
                TextColumn::make('status')
                    ->label(__('catalog::products.status'))
                    ->badge()
                    ->color(fn (ProductStatus $state): string => match ($state) {
                        ProductStatus::Draft => 'gray',
                        ProductStatus::Published => 'success',
                        ProductStatus::HiddenByAdmin => 'danger',
                    })
                    ->formatStateUsing(fn (ProductStatus $state): string => __('catalog::products.statuses.'.$state->value)),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('catalog::products.status'))
                    ->options(self::statusOptions()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::bulkAction('publish', PublishProduct::class, 'published'),
                    self::bulkAction('unpublish', UnpublishProduct::class, 'unpublished'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return collect(ProductStatus::cases())->mapWithKeys(
            fn (ProductStatus $status): array => [$status->value => __('catalog::products.statuses.'.$status->value)],
        )->all();
    }

    /**
     * A price typed in pounds and stored in piasters.
     */
    private static function moneyField(string $name): TextInput
    {
        return TextInput::make($name)
            ->label(__("catalog::products.{$name}"))
            ->numeric()
            ->minValue(0)
            ->formatStateUsing(fn ($state) => $state === null || $state === '' ? null : $state / 100)
            ->dehydrateStateUsing(fn ($state) => $state === null || $state === '' ? null : (int) round(((float) $state) * 100));
    }

    /**
     * No two Variants may share a SKU or a size and colour.
     */
    private static function variantsAreDistinct(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $items = collect($value);

            $duplicated = $items->pluck('sku')->filter()->duplicates()->isNotEmpty()
                || $items->map(fn (array $item): string => Str::lower(($item['size'] ?? '').'|'.($item['color'] ?? '')))->duplicates()->isNotEmpty();

            if ($duplicated) {
                $fail(__('catalog::products.errors.duplicate_variants'));
            }
        };
    }

    /**
     * @param  class-string  $action
     */
    private static function bulkAction(string $name, string $action, string $notification): BulkAction
    {
        return BulkAction::make($name)
            ->label(__("catalog::products.actions.{$name}"))
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records) use ($action, $notification): void {
                $failed = 0;

                foreach ($records as $record) {
                    try {
                        app($action)($record);
                    } catch (ValidationException) {
                        $failed++;
                    }
                }

                Notification::make()
                    ->title(__("catalog::products.actions.{$notification}").($failed ? " ({$failed} ✕)" : ''))
                    ->color($failed ? 'warning' : 'success')
                    ->send();
            });
    }
}
