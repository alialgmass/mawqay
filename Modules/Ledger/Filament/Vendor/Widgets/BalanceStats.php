<?php

namespace Modules\Ledger\Filament\Vendor\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Ledger\Services\BalanceService;
use Modules\Ledger\Services\ReportService;
use Modules\Sales\Enums\OrderStatus;

/**
 * Pending, Available and Paid for the Vendor Store on their dashboard, with the Orders waiting to ship.
 */
class BalanceStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $storeId = Filament::getTenant()->getKey();
        $balance = app(BalanceService::class)->forStore($storeId);
        $orders = app(ReportService::class)->ordersByStatus($storeId);

        return [
            Stat::make(__('ledger::ledger.vendor.pending'), $balance->pending()->format())->description(__('ledger::ledger.vendor.pending_hint')),
            Stat::make(__('ledger::ledger.vendor.available'), $balance->available()->format())->description(__('ledger::ledger.vendor.available_hint')),
            Stat::make(__('ledger::ledger.vendor.paid'), $balance->paid()->format()),
            Stat::make(__('ledger::reports.to_ship'), (string) (($orders[OrderStatus::Paid->value] ?? 0) + ($orders[OrderStatus::Processing->value] ?? 0))),
        ];
    }
}
