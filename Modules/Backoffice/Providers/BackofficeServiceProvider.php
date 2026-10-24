<?php

namespace Modules\Backoffice\Providers;

use Illuminate\Support\ServiceProvider;

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
    }
}
