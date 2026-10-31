<?php

namespace Modules\Theming\Filament\Admin\Resources\Themes;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use Modules\Theming\Actions\SetThemeActive;
use Modules\Theming\Filament\Admin\Resources\Themes\Pages\ListThemes;
use Modules\Theming\Models\Theme;
use Modules\Theming\Support\ThemeManager;
use Modules\Theming\Support\ThemeRegistry;

/**
 * The Platform Admin's list of registered Themes. Themes arrive by folder and
 * registration, so this resource only switches them on or off and re-scans.
 */
class ThemeResource extends Resource
{
    protected static ?string $model = Theme::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('panel.admin_nav.stores');
    }

    public static function getModelLabel(): string
    {
        return __('theming::themes.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('theming::themes.plural');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('preview')
                    ->label(__('theming::themes.preview'))
                    ->state(fn (Theme $record): string => route('theming.preview-image', ['slug' => $record->slug])),
                TextColumn::make('name')->label(__('theming::themes.name'))->searchable(),
                TextColumn::make('slug')->label(__('theming::themes.slug')),
                TextColumn::make('version')->label(__('theming::themes.version')),
                TextColumn::make('manifest.contract_version')->label(__('theming::themes.contract_version')),
                IconColumn::make('is_active')->label(__('theming::themes.is_active'))->boolean(),
            ])
            ->recordActions([
                self::activeAction('activate', true),
                self::activeAction('deactivate', false),
            ])
            ->headerActions([
                Action::make('rescan')
                    ->label(__('theming::themes.actions.rescan'))
                    ->action(function (): void {
                        $result = app(ThemeRegistry::class)->scan();
                        ThemeManager::flush();

                        $failed = count($result['failed']);

                        Notification::make()
                            ->title(__('theming::themes.actions.rescanned', ['count' => $result['registered']->count()]).($failed ? ' '.__('theming::themes.actions.rescan_failed', ['count' => $failed]) : ''))
                            ->color($failed ? 'warning' : 'success')
                            ->send();
                    }),
            ])
            ->defaultSort('slug');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListThemes::route('/'),
        ];
    }

    private static function activeAction(string $name, bool $active): Action
    {
        return Action::make($name)
            ->label(__("theming::themes.actions.{$name}"))
            ->requiresConfirmation()
            ->visible(fn (Theme $record): bool => $record->is_active !== $active)
            ->action(function (Theme $record) use ($active): void {
                try {
                    app(SetThemeActive::class)($record, $active);
                } catch (ValidationException $exception) {
                    Notification::make()->title(collect($exception->errors())->flatten()->first())->danger()->send();

                    return;
                }

                Notification::make()->title(__('theming::themes.actions.updated'))->success()->send();
            });
    }
}
