<?php

namespace Modules\Storefront\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UseArabicLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale('ar');

        return $next($request);
    }
}
