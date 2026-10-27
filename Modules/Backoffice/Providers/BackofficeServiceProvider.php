<?php

namespace Modules\Backoffice\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Backoffice\Console\WipeDemoData;

class BackofficeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(AdminPanelProvider::class);
        $this->app->register(VendorPanelProvider::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'backoffice');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'backoffice');

        if ($this->app->runningInConsole()) {
            $this->commands([WipeDemoData::class]);
        }
    }
}
