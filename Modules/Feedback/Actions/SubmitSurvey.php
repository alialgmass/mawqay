<?php

namespace Modules\Feedback\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Core\Support\StoreContext;
use Modules\Feedback\Events\LowRatingReceived;
use Modules\Feedback\Exceptions\SurveyNotAllowed;
use Modules\Feedback\Models\Survey;

/**
 * Records the Customer answer once. An overall rating of 2 or below flags it and announces a Low Rating,
 * which only informs people: nothing about the Order, its money or its status changes.
 */
class SubmitSurvey
{
    public const LOW_RATING_AT_OR_BELOW = 2;

    public function __construct(private readonly StoreContext $context) {}

    /**
     * @param  array<string, mixed>  $input  overall_rating, product_match_rating, delivery_speed_rating (1-5) and an optional comment
     *
     * @throws SurveyNotAllowed when it was already answered
     * @throws ValidationException
     */
    public function __invoke(Survey $survey, array $input): Survey
    {
        $data = Validator::make($input, [
            'overall_rating' => ['required', 'integer', 'between:1,5'],
            'product_match_rating' => ['required', 'integer', 'between:1,5'],
            'delivery_speed_rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $answered = $this->context->runAs($survey->store_id, fn (): Survey => DB::transaction(function () use ($survey, $data): Survey {
            $locked = Survey::query()->lockForUpdate()->findOrFail($survey->getKey());

            if ($locked->isSubmitted()) {
                throw new SurveyNotAllowed('This survey was already answered.');
            }

            $locked->update([
                ...$data,
                'submitted_at' => now(),
                'low_rating_flag' => $data['overall_rating'] <= self::LOW_RATING_AT_OR_BELOW,
            ]);

            return $locked;
        }));

        if ($answered->low_rating_flag) {
            LowRatingReceived::dispatch($answered);
        }

        return $answered;
    }
}
