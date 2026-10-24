<?php

namespace Modules\Storefront\Http\Controllers;

use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Storefront\Pages\HomePageData;
use Modules\Storefront\Support\StorefrontPresenter;
use Modules\Storefront\Support\ThemeRenderer;
use Modules\Tenancy\Models\Store;
use Symfony\Component\HttpFoundation\Response;

class HomeController
{
    private const LATEST_COUNT = 12;

    public function __invoke(StorefrontPresenter $presenter, ThemeRenderer $renderer): Response
    {
        $store = Store::current();

        $latest = Product::query()->published()->with('media')->latest('id')->limit(self::LATEST_COUNT)->get();
        $categories = Category::query()->shown()->topLevel()->orderBy('sort')->orderBy('id')->get();

        return $renderer->render(new HomePageData(
            $presenter->shell(),
            $presenter->seo($store->name, $store->footer_text, $store->logoUrl()),
            latest: $latest->map(fn (Product $product): array => $presenter->card($product))->all(),
            categories: $categories->map(fn (Category $category): array => $presenter->categoryLink($category))->all(),
        ));
    }
}
