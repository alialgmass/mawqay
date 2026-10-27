<?php

namespace Modules\Notifications\Listeners;

use Modules\Core\Support\StoreContext;
use Modules\Feedback\Events\SurveyInvitationDue;
use Modules\Notifications\Notifications\SurveyInvitation;

class SendSurveyInvitation
{
    public function __construct(private readonly StoreContext $context) {}

    public function handle(SurveyInvitationDue $event): void
    {
        $survey = $event->survey;

        $this->context->runAs($survey->store_id, function () use ($survey, $event): void {
            $order = $survey->order;

            $order?->customer?->notify(new SurveyInvitation($order->number, $event->url));
        });
    }
}
