<?php

namespace Modules\Ledger\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Ledger\Filament\Admin\Pages\Reports;
use Modules\Ledger\Filament\Admin\Pages\VendorBalances;
use Modules\Ledger\Filament\Admin\Resources\LedgerEntries\LedgerEntryResource;
use Modules\Ledger\Filament\Admin\Resources\Payouts\PayoutResource;
use Modules\Ledger\Filament\Admin\Widgets\OrdersByStatusChart;
use Modules\Ledger\Filament\Admin\Widgets\PlatformStats;
use Modules\Ledger\Filament\Admin\Widgets\ReconciliationWidget;
use Modules\Ledger\Filament\Admin\Widgets\TopStoresChart;
use Modules\Ledger\Filament\Vendor\Pages\Payouts;
use Modules\Ledger\Filament\Vendor\Pages\Statement;
use Modules\Ledger\Filament\Vendor\Widgets\BalanceStats;
use Modules\Ledger\Filament\Vendor\Widgets\SalesChart;
use Modules\Ledger\Filament\Vendor\Widgets\TopProductsChart;

/**
 * Contributes the books and payouts to the Admin Panel, and each Vendor their own balances, statement and payouts.
 */
class LedgerPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'ledger';
    }

    public function register(Panel $panel): void
    {
        match ($panel->getId()) {
            'admin' => $panel
                ->resources([LedgerEntryResource::class, PayoutResource::class])
                ->pages([VendorBalances::class, Reports::class])
                ->widgets([PlatformStats::class, OrdersByStatusChart::class, TopStoresChart::class, ReconciliationWidget::class]),
            'vendor' => $panel
                ->pages([Statement::class, Payouts::class])
                ->widgets([BalanceStats::class, SalesChart::class, TopProductsChart::class]),
            default => null,
        };
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
