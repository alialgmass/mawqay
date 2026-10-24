<?php

namespace Modules\Tenancy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Tenancy\Contracts\StoreResolver;
use Modules\Tenancy\Enums\StoreStatus;
use Modules\Tenancy\Models\StoreSlugRedirect;
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

        if ($store === null) {
            return $this->movedOrNotFound($request);
        }

        $this->currentStore->set($store);

        return $next($request);
    }

    /**
     * A former Store Slug redirects permanently to the current address;
     * anything else is an unknown Store.
     */
    private function movedOrNotFound(Request $request): Response
    {
        $oldSlug = $request->route('store');

        $redirect = is_string($oldSlug)
            ? StoreSlugRedirect::query()->where('old_slug', $oldSlug)->with('store')->first()
            : null;

        // A Draft Store must look non-existent, so its old address does not reveal the new one.
        if ($redirect?->store !== null && $redirect->store->status !== StoreStatus::Draft) {
            $path = substr($request->getRequestUri(), strlen('/'.$oldSlug));

            return redirect()->to('/'.$redirect->store->slug.$path, 301);
        }

        return response()->view('tenancy::not-found', [], 404);
    }
}
