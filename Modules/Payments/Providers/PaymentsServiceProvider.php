<?php

namespace Modules\Payments\Providers;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Payments\Contracts\PaymentGateway;
use Modules\Payments\Events\PaymentApproved;
use Modules\Payments\Filament\Admin\Support\OrderPaymentHistory;
use Modules\Payments\Gateways\ManualPaymentGateway;
use Modules\Payments\Listeners\NotifyVendorOfPaidOrder;
use Modules\Payments\Models\PaymentMethod;
use Modules\Payments\Policies\PaymentMethodPolicy;
use Modules\Sales\Filament\Admin\Resources\Orders\Pages\ViewOrder;

class PaymentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/payments.php', 'payments');

        $this->app->bind(PaymentGateway::class, ManualPaymentGateway::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'payments');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'payments');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Gate::policy(PaymentMethod::class, PaymentMethodPolicy::class);

        Event::listen(PaymentApproved::class, [NotifyVendorOfPaidOrder::class, 'handle']);

        FilamentView::registerRenderHook(
            PanelsRenderHook::PAGE_END,
            fn (): string => (string) $this->app->make(OrderPaymentHistory::class)->render(),
            scopes: ViewOrder::class,
        );
    }
}
