<?php

namespace Modules\Feedback\Listeners;

use Modules\Feedback\Actions\CreateSurvey;
use Modules\Sales\Events\OrderDelivered;

class CreateSurveyOnDelivery
{
    public function __construct(private readonly CreateSurvey $create) {}

    public function handle(OrderDelivered $event): void
    {
        ($this->create)($event->order);
    }
}
