<?php

namespace Modules\Auth\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Modules\Auth\Models\Address;
use Modules\Auth\Policies\AddressPolicy;
use Modules\Auth\Support\AuthVendorOnboarding;
use Modules\Tenancy\Contracts\VendorOnboarding;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/customers.php', 'customers');

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

        Gate::policy(Address::class, AddressPolicy::class);

        RateLimiter::for('customer-auth', function (Request $request) {
            $store = (string) $request->route('store');
            $identifier = (string) ($request->input('identifier') ?? $request->input('email') ?? $request->input('phone'));

            $field = $request->has('identifier') ? 'identifier' : 'email';

            $tooMany = function (Request $request, array $headers) use ($field) {
                $message = __('auth::customers.throttled', ['seconds' => $headers['Retry-After'] ?? 60]);

                return $request->expectsJson()
                    ? response()->json(['message' => $message], 429, $headers)
                    : back()->withErrors([$field => $message]);
            };

            // One bucket per identifier, plus a wider one per IP so rotating identifiers does not help.
            return [
                Limit::perMinute(5)->by($store.'|'.mb_strtolower($identifier).'|'.$request->ip())->response($tooMany),
                Limit::perMinute(30)->by($store.'|'.$request->ip())->response($tooMany),
            ];
        });
    }
}
