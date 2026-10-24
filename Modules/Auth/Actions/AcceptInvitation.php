<?php

namespace Modules\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\Invitation;

class AcceptInvitation
{
    /**
     * Sets the owner's password and spends the invitation, or returns null when
     * the link is unknown, expired, used or replaced.
     */
    public function __invoke(string $token, string $password): ?User
    {
        return DB::transaction(function () use ($token, $password): ?User {
            $invitation = Invitation::query()
                ->usable()
                ->where('token_hash', Invitation::hash($token))
                ->lockForUpdate()
                ->first();

            if ($invitation === null) {
                return null;
            }

            $owner = $invitation->user;
            $owner->forceFill(['password' => $password, 'email_verified_at' => now()])->save();
            $invitation->update(['accepted_at' => now()]);

            return $owner;
        });
    }
}
