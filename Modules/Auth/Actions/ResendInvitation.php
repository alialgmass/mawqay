<?php

namespace Modules\Auth\Actions;

use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\Invitation;
use Modules\Tenancy\Models\Store;

class ResendInvitation
{
    public function __construct(private readonly IssueInvitation $issueInvitation) {}

    /**
     * @throws ValidationException when the owner has already accepted
     */
    public function __invoke(Store $store): Invitation
    {
        $owner = $store->owners()->oldest('id')->firstOrFail();

        if (Invitation::query()->where('user_id', $owner->id)->whereNotNull('accepted_at')->exists()) {
            throw ValidationException::withMessages(['invitation' => __('auth::invitations.already_accepted')]);
        }

        return ($this->issueInvitation)($owner);
    }
}
