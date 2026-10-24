<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Modules\Auth\Actions\AcceptInvitation;
use Modules\Auth\Models\Invitation;

class InvitationController
{
    public function show(string $token): Response
    {
        $invitation = Invitation::query()->usable()->where('token_hash', Invitation::hash($token))->with('store')->first();

        if ($invitation === null) {
            return response()->view('auth::invitations.invalid', [], 410);
        }

        return response()->view('auth::invitations.accept', ['invitation' => $invitation, 'token' => $token]);
    }

    public function accept(Request $request, string $token, AcceptInvitation $acceptInvitation): Response|RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $owner = $acceptInvitation($token, $validated['password']);

        if ($owner === null) {
            return response()->view('auth::invitations.invalid', [], 410);
        }

        Auth::guard('web')->login($owner);
        $request->session()->regenerate();

        return redirect('/vendor/'.$owner->store->slug);
    }
}
