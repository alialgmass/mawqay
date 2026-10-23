<?php

namespace Modules\Tenancy\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Tenancy\Contracts\StoreResolver;
use Modules\Tenancy\Resolvers\PathStoreResolver;
use Modules\Tenancy\Support\CurrentStore;

class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tenancy.php', 'tenancy');

        $this->app->scoped(CurrentStore::class);
        $this->app->bind(StoreResolver::class, PathStoreResolver::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
