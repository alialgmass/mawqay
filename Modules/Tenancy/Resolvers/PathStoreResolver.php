<?php

namespace Modules\Tenancy\Resolvers;

use Illuminate\Http\Request;
use Modules\Tenancy\Contracts\StoreResolver;
use Modules\Tenancy\Models\Store;

class PathStoreResolver implements StoreResolver
{
    public function resolve(Request $request): ?Store
    {
        $slug = $request->route('store');

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        return Store::query()->where('slug', $slug)->first();
    }
}
