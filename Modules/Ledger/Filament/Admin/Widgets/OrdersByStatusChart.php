<?php

namespace Modules\Ledger\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use Modules\Ledger\Services\ReportService;
use Modules\Sales\Enums\OrderStatus;

class OrdersByStatusChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return __('ledger::reports.orders_by_status');
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $counts = app(ReportService::class)->ordersByStatus(null);
        $statuses = collect(OrderStatus::cases())->filter(fn (OrderStatus $status): bool => isset($counts[$status->value]));

        return [
            'datasets' => [['data' => $statuses->map(fn (OrderStatus $status): int => $counts[$status->value])->values()->all()]],
            'labels' => $statuses->map(fn (OrderStatus $status): string => __('sales::orders.statuses.'.$status->value))->values()->all(),
        ];
    }
}
