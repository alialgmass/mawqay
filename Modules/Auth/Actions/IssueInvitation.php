<?php

namespace Modules\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Auth\Mail\VendorOwnerInvitation;
use Modules\Auth\Models\Invitation;

/**
 * Sends a fresh invitation link and retires any earlier one for the same owner.
 */
class IssueInvitation
{
    public function __invoke(User $owner): Invitation
    {
        Invitation::query()->where('user_id', $owner->id)->usable()->update(['revoked_at' => now()]);

        $token = Str::random(64);

        $invitation = Invitation::query()->create([
            'user_id' => $owner->id,
            'store_id' => $owner->store_id,
            'token_hash' => Invitation::hash($token),
            'expires_at' => now()->addHours(Invitation::LIFETIME_HOURS),
        ]);

        Mail::to($owner->email)->send(new VendorOwnerInvitation($owner, $invitation, route('invitations.show', $token)));

        return $invitation;
    }
}
