<?php

namespace Modules\Feedback\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Feedback\Console\SendSurveys;
use Modules\Feedback\Listeners\CreateSurveyOnDelivery;
use Modules\Sales\Events\OrderDelivered;

class FeedbackServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'feedback');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'feedback');

        Event::listen(OrderDelivered::class, [CreateSurveyOnDelivery::class, 'handle']);

        if ($this->app->runningInConsole()) {
            $this->commands([SendSurveys::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('feedback:send-surveys')->hourly()->withoutOverlapping();
        });
    }
}
