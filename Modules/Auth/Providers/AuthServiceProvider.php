<?php

namespace Modules\Auth\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Modules\Auth\Support\AuthVendorOnboarding;
use Modules\Tenancy\Contracts\VendorOnboarding;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VendorOnboarding::class, AuthVendorOnboarding::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'auth');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'auth');

        // Only a User can hold a platform role; a Customer is always denied.
        Gate::define('platform-admin', fn (Authenticatable $actor): bool => $actor instanceof User && $actor->isPlatformAdmin());
        Gate::define('vendor-owner', fn (Authenticatable $actor): bool => $actor instanceof User && $actor->isVendorOwner());

        RateLimiter::for('customer-auth', function (Request $request) {
            $store = (string) $request->route('store');
            $identifier = (string) ($request->input('identifier') ?? $request->input('email') ?? $request->input('phone'));

            return Limit::perMinute(5)->by($store.'|'.mb_strtolower($identifier).'|'.$request->ip());
        });
    }
}
