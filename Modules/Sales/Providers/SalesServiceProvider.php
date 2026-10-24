<?php

namespace Modules\Sales\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Sales\Console\ExpireUnpaidOrders;
use Modules\Sales\Listeners\MergeGuestCart;
use Modules\Sales\Models\Order;
use Modules\Sales\Policies\OrderPolicy;

class SalesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sales.php', 'sales');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'sales');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'sales');

        Gate::policy(Order::class, OrderPolicy::class);
        Event::listen(Login::class, MergeGuestCart::class);

        if ($this->app->runningInConsole()) {
            $this->commands([ExpireUnpaidOrders::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('orders:expire')->everyFiveMinutes()->withoutOverlapping();
        });
    }
}
