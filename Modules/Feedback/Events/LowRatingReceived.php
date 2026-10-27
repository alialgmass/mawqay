<?php

namespace Modules\Feedback\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Feedback\Models\Survey;

/**
 * An Order was rated 2 or below. Listeners may only tell people: it never refunds or changes an Order.
 */
class LowRatingReceived
{
    use Dispatchable;

    public function __construct(public readonly Survey $survey) {}
}
