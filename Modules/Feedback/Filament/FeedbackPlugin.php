<?php

namespace Modules\Feedback\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Feedback\Filament\Admin\Resources\Surveys\SurveyResource;
use Modules\Feedback\Filament\Admin\Widgets\AverageRatingByStoreChart;
use Modules\Feedback\Filament\Admin\Widgets\FeedbackStats;
use Modules\Feedback\Filament\Admin\Widgets\FlaggedSurveysWidget;
use Modules\Feedback\Filament\Vendor\Pages\CustomerFeedback;
use Modules\Feedback\Filament\Vendor\Widgets\RatingStats;
use Modules\Feedback\Filament\Vendor\Widgets\RatingTrendChart;

/**
 * Contributes Surveys to the Admin Panel, and each Vendor their own Store feedback.
 */
class FeedbackPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'feedback';
    }

    public function register(Panel $panel): void
    {
        match ($panel->getId()) {
            'admin' => $panel
                ->resources([SurveyResource::class])
                ->widgets([FeedbackStats::class, AverageRatingByStoreChart::class, FlaggedSurveysWidget::class]),
            'vendor' => $panel
                ->pages([CustomerFeedback::class])
                ->widgets([RatingStats::class, RatingTrendChart::class]),
            default => null,
        };
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
