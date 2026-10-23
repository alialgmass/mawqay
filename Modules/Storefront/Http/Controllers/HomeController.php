<?php

namespace Modules\Storefront\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tenancy\Models\Store;

class HomeController
{
    public function __invoke(): Response
    {
        $store = Store::current();
        $customer = Auth::guard('customer')->user();

        return Inertia::render('storefront/Home', [
            'store' => [
                'name' => $store->name,
                'slug' => $store->slug,
            ],
            'customer' => $customer ? ['name' => $customer->name] : null,
            'tokens' => config('storefront.tokens'),
        ]);
    }
}
