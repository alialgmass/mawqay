<?php

namespace Modules\Catalog\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Catalog\Contracts\ProductSearch;
use Modules\Catalog\Search\NameProductSearch;

class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductSearch::class, NameProductSearch::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'catalog');
    }
}
