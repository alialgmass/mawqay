<?php

namespace Modules\Ledger\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use Modules\Ledger\Services\ReportService;

class TopStoresChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return __('ledger::reports.top_stores');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $rows = array_slice(app(ReportService::class)->revenueByStore(now()->subDays(29)->startOfDay(), now()->endOfDay()), 0, 5);

        return [
            'datasets' => [['label' => __('ledger::reports.gmv'), 'data' => array_map(fn (array $row): float => $row['gmv'] / 100, $rows)]],
            'labels' => array_map(fn (array $row): string => $row['store'], $rows),
        ];
    }
}
