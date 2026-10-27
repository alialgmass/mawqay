<?php

namespace Modules\Feedback\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use Modules\Feedback\Services\FeedbackReport;

class AverageRatingByStoreChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return __('feedback::feedback.average_by_store');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $rows = app(FeedbackReport::class)->averageByStore(now()->subDays(29)->startOfDay(), now()->endOfDay());

        return [
            'datasets' => [['label' => __('feedback::feedback.overall'), 'data' => array_column($rows, 'average')]],
            'labels' => array_column($rows, 'store'),
        ];
    }
}
