<?php

namespace Modules\Storefront\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Modules\Tenancy\Models\Store;

class HomeController
{
    public function __invoke(): Response
    {
        $store = Store::current();

        return Inertia::render('storefront/Home', [
            'store' => [
                'name' => $store->name,
                'slug' => $store->slug,
            ],
            'tokens' => config('storefront.tokens'),
        ]);
    }
}
