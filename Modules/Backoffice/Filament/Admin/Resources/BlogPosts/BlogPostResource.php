<?php

namespace Modules\Backoffice\Filament\Admin\Resources\BlogPosts;

use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Modules\Backoffice\Models\BlogPost;

/**
 * The Platform blog for the marketing pages: bilingual posts, written once,
 * translated by the Admin, published with a fixed date.
 */
class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('panel.admin_nav.content');
    }

    public static function getModelLabel(): string
    {
        return __('backoffice::blog.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('backoffice::blog.plural');
    }

    /**
     * @return array<string, string>
     */
    public static function categories(): array
    {
        /** @var array<string, string> $labels */
        $labels = trans('backoffice::blog.categories');

        return $labels;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make(__('backoffice::blog.sections.content'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label(__('backoffice::blog.title'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, string $operation, ?string $state): void {
                                if ($operation === 'create' && filled($state)) {
                                    $set('slug', Str::slug($state) ?: 'post-'.Str::lower(Str::random(8)));
                                }
                            }),
                        TextInput::make('title_ar')
                            ->label(__('backoffice::blog.title_ar'))
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label(__('backoffice::blog.slug'))
                            ->alphaDash()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Select::make('category')
                            ->label(__('backoffice::blog.category'))
                            ->options(self::categories())
                            ->native(false),
                        Textarea::make('excerpt')
                            ->label(__('backoffice::blog.excerpt'))
                            ->maxLength(300)
                            ->columnSpanFull(),
                        Textarea::make('excerpt_ar')
                            ->label(__('backoffice::blog.excerpt_ar'))
                            ->maxLength(300)
                            ->columnSpanFull(),
                        Textarea::make('body')
                            ->label(__('backoffice::blog.body'))
                            ->required()
                            ->autosize()
                            ->minLength(10)
                            ->columnSpanFull(),
                        Textarea::make('body_ar')
                            ->label(__('backoffice::blog.body_ar'))
                            ->autosize()
                            ->columnSpanFull(),
                    ]),
                Section::make(__('backoffice::blog.sections.publication'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        FileUpload::make('cover_path')
                            ->label(__('backoffice::blog.cover'))
                            ->image()
                            ->imageEditor()
                            ->maxSize(2048)
                            ->directory('blog-covers')
                            ->disk('public')
                            ->columnSpanFull(),
                        TextInput::make('author_name')
                            ->label(__('backoffice::blog.author'))
                            ->maxLength(255),
                        Toggle::make('is_published')
                            ->label(__('backoffice::blog.published'))
                            ->live(),
                        DatePicker::make('published_at')
                            ->label(__('backoffice::blog.published_at'))
                            ->native(false)
                            ->visible(fn (callable $get): bool => (bool) $get('is_published')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label(__('backoffice::blog.title'))->searchable()->limit(50),
                TextColumn::make('title_ar')->label(__('backoffice::blog.title_ar'))->limit(50),
                TextColumn::make('category')->label(__('backoffice::blog.category'))->badge(),
                IconColumn::make('is_published')->label(__('backoffice::blog.published'))->boolean(),
                TextColumn::make('published_at')->label(__('backoffice::blog.published_at'))->dateTime()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_published')->label(__('backoffice::blog.published')),
                SelectFilter::make('category')
                    ->label(__('backoffice::blog.category'))
                    ->options(self::categories())
                    ->native(false),
            ])
            ->actions([
                //
            ])
            ->defaultSort('published_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'edit' => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }
}
