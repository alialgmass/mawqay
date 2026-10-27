<?php

namespace Modules\Notifications\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Feedback\Events\LowRatingReceived;
use Modules\Feedback\Events\SurveyInvitationDue;
use Modules\Ledger\Events\PayoutRequested;
use Modules\Notifications\Listeners\NotifyAdminsOfPayoutRequest;
use Modules\Notifications\Listeners\NotifyCustomerOfPlacedOrder;
use Modules\Notifications\Listeners\NotifyOfLowRating;
use Modules\Notifications\Listeners\SendSurveyInvitation;
use Modules\Sales\Events\OrderPlaced;

class NotificationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'notifications');

        Event::listen(OrderPlaced::class, [NotifyCustomerOfPlacedOrder::class, 'handle']);
        Event::listen(PayoutRequested::class, [NotifyAdminsOfPayoutRequest::class, 'handle']);
        Event::listen(LowRatingReceived::class, [NotifyOfLowRating::class, 'handle']);
        Event::listen(SurveyInvitationDue::class, [SendSurveyInvitation::class, 'handle']);
    }
}
