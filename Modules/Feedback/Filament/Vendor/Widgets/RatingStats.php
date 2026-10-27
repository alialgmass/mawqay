<?php

namespace Modules\Feedback\Filament\Vendor\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Feedback\Services\FeedbackReport;

/**
 * The Store average ratings for the last 30 days.
 */
class RatingStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $averages = app(FeedbackReport::class)->averages(Filament::getTenant()->getKey(), now()->subDays(29)->startOfDay(), now()->endOfDay());
        $show = fn (?float $value): string => $value === null ? '—' : (string) $value;

        return [
            Stat::make(__('feedback::feedback.overall'), $show($averages['overall']))->description(__('feedback::feedback.answers', ['count' => $averages['count']])),
            Stat::make(__('feedback::feedback.product_match'), $show($averages['product_match'])),
            Stat::make(__('feedback::feedback.delivery_speed'), $show($averages['delivery_speed'])),
        ];
    }
}
