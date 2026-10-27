<?php

namespace Modules\Ledger\Filament\Vendor\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Modules\Ledger\Services\ReportService;

class TopProductsChart extends ChartWidget
{
    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return __('ledger::reports.top_products');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $rows = app(ReportService::class)->topProducts(Filament::getTenant()->getKey(), now()->subDays(29)->startOfDay(), now()->endOfDay());

        return [
            'datasets' => [['label' => __('ledger::reports.units_sold'), 'data' => array_column($rows, 'quantity')]],
            'labels' => array_column($rows, 'name'),
        ];
    }
}
