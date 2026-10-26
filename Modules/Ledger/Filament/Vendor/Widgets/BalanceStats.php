<?php

namespace Modules\Ledger\Filament\Vendor\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Ledger\Services\BalanceService;

/**
 * Pending, Available and Paid for the Vendor Store on their dashboard.
 */
class BalanceStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $balance = app(BalanceService::class)->forStore(Filament::getTenant()->getKey());

        return [
            Stat::make(__('ledger::ledger.vendor.pending'), $balance->pending()->format())->description(__('ledger::ledger.vendor.pending_hint')),
            Stat::make(__('ledger::ledger.vendor.available'), $balance->available()->format())->description(__('ledger::ledger.vendor.available_hint')),
            Stat::make(__('ledger::ledger.vendor.paid'), $balance->paid()->format()),
        ];
    }
}
