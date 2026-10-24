<?php

namespace Modules\Theming\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Tenancy\Models\Store;
use Modules\Theming\Console\ListThemesCommand;
use Modules\Theming\Console\RegisterThemeCommand;
use Modules\Theming\Models\Theme;
use Modules\Theming\Support\ThemeManager;
use Modules\Theming\Support\ThemeRegistry;

class ThemingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/theming.php', 'theming');

        $this->app->singleton(ThemeRegistry::class);
        $this->app->singleton(ThemeManager::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'theming');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'theming');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([RegisterThemeCommand::class, ListThemesCommand::class]);
        }

        Theme::saved(fn () => ThemeManager::flush());
        Theme::deleted(fn () => ThemeManager::flush());
        Store::saved(function (Store $store): void {
            if ($store->wasChanged(['theme_id', 'theme_settings'])) {
                ThemeManager::flushStore($store->id);
            }
        });
    }
}
