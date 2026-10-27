<?php

namespace Modules\Ledger\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Core\Money\Money;
use Modules\Ledger\Services\ReportService;

/**
 * The Platform at a glance: sales and Commission for 30 days, and the queues waiting on an Admin.
 */
class PlatformStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $reports = app(ReportService::class);
        $from = now()->subDays(29)->startOfDay();
        $until = now()->endOfDay();
        $payouts = $reports->payoutsAwaiting();

        return [
            Stat::make(__('ledger::reports.gmv_30'), Money::fromPiasters($reports->gmv(null, $from, $until))->format()),
            Stat::make(__('ledger::reports.commission_30'), Money::fromPiasters($reports->commissionRevenue(null, $from, $until))->format()),
            Stat::make(__('ledger::reports.receipts_awaiting'), (string) $reports->receiptsAwaiting()),
            Stat::make(__('ledger::reports.payouts_awaiting'), (string) $payouts['count'])->description(Money::fromPiasters($payouts['amount'])->format()),
        ];
    }
}
