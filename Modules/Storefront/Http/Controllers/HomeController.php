<?php

namespace Modules\Storefront\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Storefront\Support\StorefrontPresenter;
use Modules\Tenancy\Models\Store;

class HomeController
{
    private const LATEST_COUNT = 12;

    public function __invoke(StorefrontPresenter $presenter): Response
    {
        $store = Store::current();

        $latest = Product::query()->published()->with('media')->latest('id')->limit(self::LATEST_COUNT)->get();
        $categories = Category::query()->shown()->topLevel()->orderBy('sort')->orderBy('id')->get();

        return Inertia::render('storefront/Home', [
            ...$presenter->shell($store),
            'latest' => $latest->map(fn (Product $product): array => $presenter->card($product))->all(),
            'categories' => $categories->map(fn (Category $category): array => $presenter->categoryLink($category))->all(),
            'seo' => ['title' => $store->name, 'description' => $store->footer_text, 'image' => $store->logoUrl()],
        ]);
    }
}
