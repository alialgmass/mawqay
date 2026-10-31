<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Actions\CreateStoreWithOwner;

/**
 * The public vendor signup on the marketing landing page: a future Vendor
 * gives their Store and owner details and receives an invitation email; the
 * Store stays a Draft until they finish onboarding.
 */
class StoreRegistrationController extends Controller
{
    public function __construct(private readonly CreateStoreWithOwner $createStoreWithOwner) {}

    public function store(Request $request): RedirectResponse
    {
        if (filled($request->string('website'))) {
            // Honeypot tripped: look successful so bots learn nothing.
            return redirect()->route('home')->with('status', __('app.landing.register.success'));
        }

        try {
            ($this->createStoreWithOwner)($this->fields($request));
        } catch (ValidationException $exception) {
            // The form names its fields store_name / store_slug; surface the
            // action's validation errors under those same names.
            throw ValidationException::withMessages($this->renamed($exception->errors()));
        }

        return redirect()->route('home')->with('status', __('app.landing.register.success'));
    }

    /**
     * @return array{name: string, slug: string, owner_name: string, owner_email: string, owner_phone?: ?string}
     */
    private function fields(Request $request): array
    {
        return [
            'name' => $request->string('store_name')->toString(),
            'slug' => $request->string('store_slug')->toString(),
            'owner_name' => $request->string('owner_name')->toString(),
            'owner_email' => $request->string('owner_email')->toString(),
            'owner_phone' => $request->filled('owner_phone') ? $request->string('owner_phone')->toString() : null,
        ];
    }

    /**
     * Keeps the action's message objects, folders under the form's field names.
     *
     * @param  array<string, list<string>>  $errors
     * @return array<string, list<string>>
     */
    private function renamed(array $errors): array
    {
        $mapping = [
            'name' => 'store_name',
            'slug' => 'store_slug',
        ];

        $output = [];

        foreach ($errors as $key => $messages) {
            $output[$mapping[$key] ?? $key] = $messages;
        }

        return $output;
    }
}
