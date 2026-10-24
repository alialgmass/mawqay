<?php

namespace Modules\Storefront\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Contracts\ProductSearch;
use Modules\Catalog\Models\Product;
use Modules\Storefront\Support\StorefrontPresenter;
use Modules\Tenancy\Models\Store;

class SearchController
{
    private const PER_PAGE = 12;

    public function __invoke(Request $request, StorefrontPresenter $presenter, ProductSearch $search): Response
    {
        $term = trim((string) $request->query('q', ''));

        $products = $term === ''
            ? Product::query()->whereRaw('1 = 0')->paginate(self::PER_PAGE)
            : $search->search($term)->with('media')->latest('id')->paginate(self::PER_PAGE)->withQueryString();

        return Inertia::render('storefront/Search', [
            ...$presenter->shell(Store::current()),
            'query' => $term,
            'products' => $presenter->cards($products),
            'breadcrumbs' => $presenter->breadcrumbs([['name' => __('storefront::pages.search'), 'url' => null]]),
            'seo' => ['title' => __('storefront::pages.search').' | '.Store::current()->name, 'description' => null, 'image' => null],
        ]);
    }
}
