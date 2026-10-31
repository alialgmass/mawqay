<?php

namespace Modules\Backoffice\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Backoffice\Console\WipeDemoData;
use Modules\Backoffice\Models\BlogPost;
use Modules\Backoffice\Policies\BlogPostPolicy;

class BackofficeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(AdminPanelProvider::class);
        $this->app->register(VendorPanelProvider::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'backoffice');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'backoffice');

        Gate::policy(BlogPost::class, BlogPostPolicy::class);

        if ($this->app->runningInConsole()) {
            $this->commands([WipeDemoData::class]);
        }
    }
}
