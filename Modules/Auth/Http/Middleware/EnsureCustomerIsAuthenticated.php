<?php

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Support\IntendedUrl;
use Modules\Tenancy\Models\Store;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends a signed-out visitor to the Store's login page, remembering the page
 * they wanted so they come back to it after logging in.
 */
class EnsureCustomerIsAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('customer')->check()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $store = Store::current();

        if ($request->isMethod('GET')) {
            IntendedUrl::remember($request->getRequestUri(), $store);
        }

        return redirect("/{$store->slug}/login");
    }
}
