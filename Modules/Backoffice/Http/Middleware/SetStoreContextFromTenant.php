<?php

namespace Modules\Backoffice\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Modules\Core\Support\StoreContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes the Vendor Panel's tenant the current Store, so the automatic Store
 * scope applies as a second layer on top of Filament's own tenancy.
 */
class SetStoreContextFromTenant
{
    public function __construct(private readonly StoreContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        if ($tenant !== null) {
            $this->context->set($tenant->getKey());
        }

        return $next($request);
    }
}
