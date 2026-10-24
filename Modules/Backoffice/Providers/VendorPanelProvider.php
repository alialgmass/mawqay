<?php

namespace Modules\Backoffice\Providers;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Modules\Auth\Filament\AuthPlugin;
use Modules\Backoffice\Filament\Vendor\Pages\StoreIdentity;
use Modules\Backoffice\Filament\Vendor\Pages\VendorDashboard;
use Modules\Backoffice\Http\Middleware\SetStoreContextFromTenant;
use Modules\Backoffice\Http\Middleware\UseArabicLocale;
use Modules\Catalog\Filament\CatalogPlugin;
use Modules\Tenancy\Models\Store;
use Modules\Theming\Filament\ThemingPlugin;

class VendorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('vendor')
            ->path('vendor')
            ->login()
            ->tenant(Store::class, slugAttribute: 'slug')
            ->tenantMenu(false)
            ->plugin(CatalogPlugin::make())
            ->plugin(ThemingPlugin::make())
            ->plugin(AuthPlugin::make())
            ->colors([
                'primary' => Color::Rose,
                'gray' => Color::Slate,
            ])
            ->font('Inter')
            ->maxContentWidth('full')
            ->sidebarCollapsibleOnDesktop()
            ->brandName(fn (): string => __('panel.vendor_brand'))
            ->pages([
                VendorDashboard::class,
                StoreIdentity::class,
            ])
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                UseArabicLocale::class,
            ])
            ->tenantMiddleware([
                SetStoreContextFromTenant::class,
            ], isPersistent: true)
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
