<?php

namespace Modules\Backoffice\Providers;

use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
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
use Modules\Backoffice\Filament\Admin\Pages\PlatformSettings;
use Modules\Backoffice\Filament\Admin\Pages\SystemHealth;
use Modules\Backoffice\Filament\Admin\Resources\AuditEntries\AuditEntryResource;
use Modules\Backoffice\Filament\BlogPlugin;
use Modules\Backoffice\Http\Middleware\UseArabicLocale;
use Modules\Catalog\Filament\CatalogPlugin;
use Modules\Feedback\Filament\FeedbackPlugin;
use Modules\Ledger\Filament\LedgerPlugin;
use Modules\Payments\Filament\PaymentsPlugin;
use Modules\Sales\Filament\SalesPlugin;
use Modules\Tenancy\Filament\TenancyPlugin;
use Modules\Theming\Filament\ThemingPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->multiFactorAuthentication(
                [AppAuthentication::make()->recoverable()],
                isRequired: fn (): bool => (bool) config('security.admin_mfa_required'),
            )
            ->navigationGroups([
                NavigationGroup::make(fn (): string => __('panel.admin_nav.sales')),
                NavigationGroup::make(fn (): string => __('panel.admin_nav.stores')),
                NavigationGroup::make(fn (): string => __('panel.admin_nav.finance')),
                NavigationGroup::make(fn (): string => __('panel.admin_nav.content')),
                NavigationGroup::make(fn (): string => __('panel.admin_nav.system')),
            ])
            ->colors([
                'primary' => Color::Teal,
                'gray' => Color::Slate,
                'warning' => Color::Amber,
            ])
            ->font('Inter')
            ->maxContentWidth('full')
            ->sidebarCollapsibleOnDesktop()
            ->unsavedChangesAlerts()
            ->brandName(fn (): string => __('panel.brand'))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->plugin(TenancyPlugin::make())
            ->plugin(CatalogPlugin::make())
            ->plugin(ThemingPlugin::make())
            ->plugin(AuthPlugin::make())
            ->plugin(SalesPlugin::make())
            ->plugin(PaymentsPlugin::make())
            ->plugin(LedgerPlugin::make())
            ->plugin(FeedbackPlugin::make())
            ->plugin(BlogPlugin::make())
            ->resources([AuditEntryResource::class])
            ->pages([
                Dashboard::class,
                PlatformSettings::class,
                SystemHealth::class,
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
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
