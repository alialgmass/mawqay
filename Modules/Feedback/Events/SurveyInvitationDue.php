<?php

namespace Modules\Feedback\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Feedback\Models\Survey;

/**
 * A Survey is due and its Customer can be emailed the signed link.
 */
class SurveyInvitationDue
{
    use Dispatchable;

    public function __construct(public readonly Survey $survey, public readonly string $url) {}
}
