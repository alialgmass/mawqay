<?php

namespace Modules\Tenancy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Tenancy\Contracts\StoreResolver;
use Modules\Tenancy\Support\CurrentStore;
use Symfony\Component\HttpFoundation\Response;

class IdentifyStore
{
    public function __construct(
        private readonly StoreResolver $resolver,
        private readonly CurrentStore $currentStore,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $store = $this->resolver->resolve($request);

        abort_if($store === null, 404);

        $this->currentStore->set($store);

        return $next($request);
    }
}
