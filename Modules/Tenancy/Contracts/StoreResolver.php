<?php

namespace Modules\Tenancy\Contracts;

use Illuminate\Http\Request;
use Modules\Tenancy\Models\Store;

interface StoreResolver
{
    /**
     * Work out which Store a request is for, or null when none matches.
     */
    public function resolve(Request $request): ?Store;
}
