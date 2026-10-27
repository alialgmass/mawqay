<?php

namespace Modules\Storefront\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Modules\Feedback\Actions\SubmitSurvey;
use Modules\Feedback\Exceptions\SurveyNotAllowed;
use Modules\Feedback\Models\Survey;
use Modules\Feedback\Services\SurveyLinks;
use Modules\Storefront\Pages\SurveyPageData;
use Modules\Storefront\Support\StorefrontPresenter;
use Modules\Storefront\Support\ThemeRenderer;
use Modules\Tenancy\Models\Store;
use Symfony\Component\HttpFoundation\Response;

/**
 * The public survey page, reached only through a signed link. A tampered, expired or unknown link shows
 * a plain state page instead of an error, and a Survey can be answered once.
 */
class SurveyController
{
    public function __construct(
        private readonly StorefrontPresenter $presenter,
        private readonly ThemeRenderer $renderer,
        private readonly SurveyLinks $links,
    ) {}

    public function show(Request $request, string $store, string $token): Response
    {
        $problem = $this->problem($request);

        if ($problem !== null) {
            return $this->state($problem)->setStatusCode($problem === 'expired' ? 410 : 403);
        }

        $survey = Survey::query()->with('order')->where('token', $token)->first();

        if ($survey === null) {
            return $this->state('invalid')->setStatusCode(403);
        }

        if ($survey->isSubmitted()) {
            return $this->state($request->session()->pull('survey_thanks') ? 'thanks' : 'answered', $survey->order?->number);
        }

        return $this->state('form', $survey->order?->number, $this->links->submit($survey, $this->expiry($request)));
    }

    public function submit(Request $request, string $store, string $token, SubmitSurvey $submit): Response|RedirectResponse
    {
        $problem = $this->problem($request);

        if ($problem !== null) {
            return $this->state($problem)->setStatusCode($problem === 'expired' ? 410 : 403);
        }

        $survey = Survey::query()->where('token', $token)->first();

        if ($survey === null) {
            return $this->state('invalid')->setStatusCode(403);
        }

        try {
            $submit($survey, $request->only(['overall_rating', 'product_match_rating', 'delivery_speed_rating', 'comment']));
            $request->session()->flash('survey_thanks', true);
        } catch (SurveyNotAllowed) {
            // Already answered: the page tells them so.
        }

        return redirect($this->links->show($survey, $this->expiry($request)));
    }

    /**
     * @return 'invalid'|'expired'|null
     */
    private function problem(Request $request): ?string
    {
        if (! URL::hasCorrectSignature($request, absolute: true)) {
            return 'invalid';
        }

        return URL::signatureHasNotExpired($request) ? null : 'expired';
    }

    private function expiry(Request $request): Carbon
    {
        return Carbon::createFromTimestamp((int) $request->query('expires'));
    }

    private function state(string $state, ?string $orderNumber = null, ?string $submitUrl = null): Response
    {
        $title = __('storefront::pages.survey').' | '.Store::current()->name;

        return $this->renderer->render(new SurveyPageData($this->presenter->shell(), $this->presenter->seo($title), $state, $orderNumber, $submitUrl));
    }
}
