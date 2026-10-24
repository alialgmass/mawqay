<?php

namespace Modules\Storefront\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Storefront\Support\StorefrontPresenter;

class StorefrontServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/storefront.php', 'storefront');

        $this->app->scoped(StorefrontPresenter::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'storefront');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
