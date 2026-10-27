<?php

namespace Modules\Tenancy\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Support\Audit;
use Modules\Tenancy\Contracts\StoreResolver;
use Modules\Tenancy\Models\Store;
use Modules\Tenancy\Policies\StorePolicy;
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
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'tenancy');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tenancy');

        Gate::policy(Store::class, StorePolicy::class);

        Store::updated(function (Store $store): void {
            foreach (['commission_rate', 'slug'] as $field) {
                if ($store->wasChanged($field)) {
                    Audit::record("store_{$field}_changed", $store, [$field => $store->getOriginal($field)], [$field => $store->getAttribute($field)], null, $store->id);
                }
            }
        });
    }
}
