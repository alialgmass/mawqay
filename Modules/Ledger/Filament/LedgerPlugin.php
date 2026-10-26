<?php

namespace Modules\Ledger\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Ledger\Filament\Admin\Pages\VendorBalances;
use Modules\Ledger\Filament\Admin\Resources\LedgerEntries\LedgerEntryResource;
use Modules\Ledger\Filament\Admin\Widgets\ReconciliationWidget;
use Modules\Ledger\Filament\Vendor\Pages\Statement;
use Modules\Ledger\Filament\Vendor\Widgets\BalanceStats;

/**
 * Contributes the books to the Admin Panel, and each Vendor their own balances and statement.
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
                ->resources([LedgerEntryResource::class])
                ->pages([VendorBalances::class])
                ->widgets([ReconciliationWidget::class]),
            'vendor' => $panel
                ->pages([Statement::class])
                ->widgets([BalanceStats::class]),
            default => null,
        };
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
