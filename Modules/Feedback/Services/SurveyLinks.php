<?php

namespace Modules\Feedback\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\URL;
use Modules\Feedback\Models\Survey;
use Modules\Tenancy\Models\Store;

/**
 * The one place survey links are made, so expiry and tamper rules are the same for emails,
 * the Order page and the links a Vendor or Admin copies.
 */
class SurveyLinks
{
    public const VALID_DAYS = 30;

    public function show(Survey $survey, ?CarbonInterface $expires = null): string
    {
        return URL::temporarySignedRoute('storefront.survey', $expires ?? now()->addDays(self::VALID_DAYS), $this->parameters($survey));
    }

    public function submit(Survey $survey, CarbonInterface $expires): string
    {
        return URL::temporarySignedRoute('storefront.survey.submit', $expires, $this->parameters($survey));
    }

    /**
     * @return array{store: string, token: string}
     */
    private function parameters(Survey $survey): array
    {
        return ['store' => (string) Store::query()->whereKey($survey->store_id)->value('slug'), 'token' => $survey->token];
    }
}
