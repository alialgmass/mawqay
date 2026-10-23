<?php

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Tenancy\Models\Store;
use Symfony\Component\HttpFoundation\Response;

/**
 * A Customer belongs to one Store. If the session carries a Customer from a
 * different Store, treat the visitor as signed out here without ending their
 * session in the Store they actually belong to.
 */
class EnsureCustomerBelongsToStore
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('customer');
        $customer = $guard->user();

        if ($customer !== null && $customer->store_id !== Store::current()?->id) {
            $guard->forgetUser();
        }

        return $next($request);
    }
}
