<?php

namespace Modules\Feedback\Filament\Vendor\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Modules\Feedback\Services\FeedbackReport;

class RatingTrendChart extends ChartWidget
{
    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return __('feedback::feedback.trend');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = app(FeedbackReport::class)->trend(Filament::getTenant()->getKey(), now()->subDays(29)->startOfDay(), now()->endOfDay());

        return [
            'datasets' => [['label' => __('feedback::feedback.overall'), 'data' => array_values($days), 'spanGaps' => true]],
            'labels' => array_keys($days),
        ];
    }
}
