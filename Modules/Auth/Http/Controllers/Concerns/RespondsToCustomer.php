<?php

namespace Modules\Auth\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Auth\Support\IntendedUrl;
use Modules\Tenancy\Models\Store;

/**
 * The same action answers API-style clients with JSON and the Storefront
 * pages with a redirect and a flash message.
 */
trait RespondsToCustomer
{
    /**
     * @param  array<string, mixed>  $json
     */
    protected function respond(Request $request, array $json, string $redirectTo, ?string $status = null, int $code = 200): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json($json, $code);
        }

        $redirect = redirect($redirectTo);

        return $status === null ? $redirect : $redirect->with('status', $status);
    }

    /**
     * Where to go after logging in: the page asked for, else the account page.
     */
    protected function afterLoginUrl(Request $request): string
    {
        $store = Store::current();

        return IntendedUrl::sanitize($request->input('redirect'), $store)
            ?? IntendedUrl::pull($store)
            ?? "/{$store->slug}/account";
    }

    protected function storeUrl(string $path): string
    {
        return '/'.Store::current()->slug.$path;
    }
}
