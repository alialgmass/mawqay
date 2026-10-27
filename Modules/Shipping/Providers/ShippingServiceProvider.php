<?php

namespace Modules\Shipping\Providers;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Sales\Contracts\Fulfilment;
use Modules\Sales\Filament\Admin\Resources\Orders\Pages\ViewOrder as AdminViewOrder;
use Modules\Sales\Filament\Vendor\Resources\Orders\Pages\ViewOrder as VendorViewOrder;
use Modules\Shipping\Contracts\ShippingProvider;
use Modules\Shipping\Http\Controllers\PackingSlipController;
use Modules\Shipping\Services\OrderFulfilment;
use Modules\Shipping\Support\ShipmentPanel;

class ShippingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/shipping.php', 'shipping');

        $this->app->bind(ShippingProvider::class, fn ($app) => $app->make(config('shipping.provider')));
        $this->app->bind(Fulfilment::class, OrderFulfilment::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'shipping');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'shipping');

        Route::middleware(['web', 'auth'])
            ->get('/vendor/orders/{order}/packing-slip', PackingSlipController::class)
            ->whereNumber('order')
            ->name('shipping.packing-slip');

        foreach ([AdminViewOrder::class, VendorViewOrder::class] as $page) {
            FilamentView::registerRenderHook(
                PanelsRenderHook::PAGE_END,
                fn (): string => (string) $this->app->make(ShipmentPanel::class)->render(),
                scopes: $page,
            );
        }
    }
}
