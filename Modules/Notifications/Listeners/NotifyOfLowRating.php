<?php

namespace Modules\Notifications\Listeners;

use App\Models\User;
use Modules\Auth\Enums\UserRole;
use Modules\Core\Support\StoreContext;
use Modules\Feedback\Events\LowRatingReceived;
use Modules\Notifications\Notifications\LowRating;

/**
 * Tells exactly the Platform Admins and the Vendor Owners of the rated Store. Nobody else, and nothing else happens.
 */
class NotifyOfLowRating
{
    public function __construct(private readonly StoreContext $context) {}

    public function handle(LowRatingReceived $event): void
    {
        $survey = $event->survey;
        $number = (string) $this->context->runAs($survey->store_id, fn () => $survey->order?->number);

        User::query()->where('role', UserRole::Admin->value)->get()
            ->each(fn (User $admin) => $admin->notify(new LowRating($number, (int) $survey->overall_rating, url('/admin/surveys/'.$survey->id))));

        User::query()->where('role', UserRole::VendorOwner->value)->where('store_id', $survey->store_id)->get()
            ->each(fn (User $owner) => $owner->notify(new LowRating($number, (int) $survey->overall_rating, url('/vendor'))));
    }
}
