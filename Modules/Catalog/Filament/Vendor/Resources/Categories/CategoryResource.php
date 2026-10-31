<?php

namespace Modules\Catalog\Filament\Vendor\Resources\Categories;

use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Filament\Vendor\Resources\Categories\Pages\ManageCategories;
use Modules\Catalog\Models\Category;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('panel.nav.catalog');
    }

    public static function getModelLabel(): string
    {
        return __('catalog::categories.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('catalog::categories.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('catalog::categories.name'))
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
            TextInput::make('slug')
                ->label(__('catalog::categories.slug'))
                ->required()
                ->maxLength(255)
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('store_id', Filament::getTenant()?->getKey()),
                ),
            Select::make('parent_id')
                ->label(__('catalog::categories.parent'))
                ->options(fn (?Category $record): array => Category::query()
                    ->topLevel()
                    ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                    ->orderBy('sort')
                    ->pluck('name', 'id')
                    ->all())
                ->disabled(fn (?Category $record): bool => $record?->children()->exists() ?? false)
                ->placeholder('—'),
            Toggle::make('is_active')
                ->label(__('catalog::categories.is_active'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('parent')->withCount('products'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('catalog::categories.name'))
                    ->formatStateUsing(fn (string $state, Category $record): string => $record->parent ? "{$record->parent->name} / {$state}" : $state)
                    ->searchable(),
                TextColumn::make('products_count')->label(__('catalog::categories.products_count')),
                IconColumn::make('is_active')->label(__('catalog::categories.is_active'))->boolean(),
            ])
            ->reorderable('sort')
            ->defaultSort('sort')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->action(function (Category $record): void {
                    try {
                        $record->delete();
                    } catch (ValidationException $exception) {
                        Notification::make()->title(collect($exception->errors())->flatten()->first())->danger()->send();
                    }
                }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }
}
