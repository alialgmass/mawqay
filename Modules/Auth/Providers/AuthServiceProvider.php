<?php

namespace Modules\Auth\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        RateLimiter::for('customer-auth', function (Request $request) {
            $store = (string) $request->route('store');
            $identifier = (string) ($request->input('identifier') ?? $request->input('email') ?? $request->input('phone'));

            return Limit::perMinute(5)->by($store.'|'.mb_strtolower($identifier).'|'.$request->ip());
        });
    }
}
