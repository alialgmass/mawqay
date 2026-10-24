<?php

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Support\CustomerSession;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs a Customer out when their account was deactivated or their password
 * changed since this session began.
 */
class EnsureCustomerSessionIsValid
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = Auth::guard('customer')->user();

        if ($customer !== null && (! $customer->is_active || ! CustomerSession::isCurrent($customer))) {
            CustomerSession::end();
        }

        return $next($request);
    }
}
