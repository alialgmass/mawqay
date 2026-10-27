<?php

namespace Modules\Feedback\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Modules\Feedback\Services\FeedbackReport;

/**
 * Platform-wide feedback for 30 days: average rating, response rate and how many Surveys are flagged.
 */
class FeedbackStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $report = app(FeedbackReport::class);
        $from = now()->subDays(29)->startOfDay();
        $until = now()->endOfDay();
        $averages = $report->averages(null, $from, $until);
        $rate = $report->responseRate(null, $from, $until);

        return [
            Stat::make(__('feedback::feedback.average_30'), $averages['overall'] === null ? '—' : (string) $averages['overall'])->description(__('feedback::feedback.answers', ['count' => $averages['count']])),
            Stat::make(__('feedback::feedback.response_rate'), $rate === null ? '—' : $rate.'%'),
            Stat::make(__('feedback::feedback.flagged'), (string) DB::table('satisfaction_surveys')->where('low_rating_flag', true)->count()),
        ];
    }
}
