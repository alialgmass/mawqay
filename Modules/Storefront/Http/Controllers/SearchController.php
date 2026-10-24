<?php

namespace Modules\Storefront\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Catalog\Contracts\ProductSearch;
use Modules\Catalog\Models\Product;
use Modules\Storefront\Pages\SearchPageData;
use Modules\Storefront\Support\StorefrontPresenter;
use Modules\Storefront\Support\ThemeRenderer;
use Modules\Tenancy\Models\Store;
use Symfony\Component\HttpFoundation\Response;

class SearchController
{
    private const PER_PAGE = 12;

    public function __invoke(Request $request, StorefrontPresenter $presenter, ThemeRenderer $renderer, ProductSearch $search): Response
    {
        $term = trim((string) $request->query('q', ''));

        $products = $term === ''
            ? Product::query()->whereRaw('1 = 0')->paginate(self::PER_PAGE)
            : $search->search($term)->with('media')->latest('id')->paginate(self::PER_PAGE)->withQueryString();

        return $renderer->render(new SearchPageData(
            $presenter->shell(),
            $presenter->seo(__('storefront::pages.search').' | '.Store::current()->name),
            query: $term,
            products: $presenter->cards($products),
            breadcrumbs: $presenter->breadcrumbs([['name' => __('storefront::pages.search'), 'url' => null]]),
        ));
    }
}
