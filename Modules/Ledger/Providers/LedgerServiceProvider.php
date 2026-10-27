<?php

namespace Modules\Ledger\Providers;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Ledger\Console\Reconcile;
use Modules\Ledger\Console\ReleaseAvailableFunds;
use Modules\Ledger\Filament\Vendor\Support\OrderNet;
use Modules\Ledger\Listeners\PostApprovedPayment;
use Modules\Ledger\Services\BalanceService;
use Modules\Ledger\Services\OrderMoneyReverser;
use Modules\Payments\Events\PaymentApproved;
use Modules\Sales\Contracts\ReversesOrderMoney;
use Modules\Sales\Filament\Vendor\Resources\Orders\Pages\ViewOrder;

class LedgerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BalanceService::class);
        $this->app->bind(ReversesOrderMoney::class, OrderMoneyReverser::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'ledger');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'ledger');

        Event::listen(PaymentApproved::class, [PostApprovedPayment::class, 'handle']);

        FilamentView::registerRenderHook(
            PanelsRenderHook::PAGE_END,
            fn (): string => (string) $this->app->make(OrderNet::class)->render(),
            scopes: ViewOrder::class,
        );

        if ($this->app->runningInConsole()) {
            $this->commands([ReleaseAvailableFunds::class, Reconcile::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('ledger:release-funds')->hourly()->withoutOverlapping();
            $schedule->command('ledger:reconcile')->daily()->withoutOverlapping();
        });
    }
}
