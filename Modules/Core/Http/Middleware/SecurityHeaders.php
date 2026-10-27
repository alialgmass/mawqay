<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds the browser protections every response should carry. The Storefront gets an enforced content
 * security policy; the panels, whose front end needs relaxed script rules, get a report-only one.
 * Local development is left without a policy so the asset dev server keeps working.
 */
class SecurityHeaders
{
    private const STOREFRONT_POLICY = "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com; font-src 'self' data: https://fonts.bunny.net https://fonts.gstatic.com; img-src 'self' data: blob: https:; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'";

    private const PANEL_POLICY = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com; font-src 'self' data: https://fonts.bunny.net https://fonts.gstatic.com; img-src 'self' data: blob: https:; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'";

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (app()->isProduction() && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (! app()->environment('local')) {
            $response->headers->set($this->isPanel($request) ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy', $this->isPanel($request) ? self::PANEL_POLICY : self::STOREFRONT_POLICY);
        }

        return $response;
    }

    private function isPanel(Request $request): bool
    {
        return $request->is('admin', 'admin/*', 'vendor', 'vendor/*', 'livewire', 'livewire/*', 'filament/*');
    }
}
