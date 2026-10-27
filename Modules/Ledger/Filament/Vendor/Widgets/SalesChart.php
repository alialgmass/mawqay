<?php

namespace Modules\Ledger\Filament\Vendor\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Modules\Ledger\Services\ReportService;

/**
 * The Store's sales, net of refunds, for each of the last 30 days.
 */
class SalesChart extends ChartWidget
{
    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return __('ledger::reports.sales_30');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = app(ReportService::class)->salesByDay(Filament::getTenant()->getKey(), now()->subDays(29)->startOfDay(), now()->endOfDay());

        return [
            'datasets' => [['label' => __('ledger::reports.gmv'), 'data' => array_map(fn (array $day): float => $day['gmv'] / 100, array_values($days))]],
            'labels' => array_keys($days),
        ];
    }
}
