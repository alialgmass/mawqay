<?php

namespace Modules\Storefront\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Storefront\Pages\CategoryPageData;
use Modules\Storefront\Support\StorefrontPresenter;
use Modules\Storefront\Support\ThemeRenderer;
use Modules\Tenancy\Models\Store;
use Symfony\Component\HttpFoundation\Response;

class CategoryController
{
    private const PER_PAGE = 12;

    private const SORTS = ['newest', 'price_asc', 'price_desc'];

    public function __invoke(Request $request, StorefrontPresenter $presenter, ThemeRenderer $renderer, string $store, string $category): Response
    {
        $current = Category::query()->shown()->with('parent')->where('slug', $category)->first();

        if ($current === null) {
            return $renderer->notFound();
        }

        $sort = in_array($request->query('sort'), self::SORTS, true) ? $request->query('sort') : 'newest';

        $products = Product::query()
            ->published()
            ->with('media')
            ->whereIn('category_id', $current->selfAndChildIds())
            ->tap(fn (Builder $query) => $this->sorted($query, $sort))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $trail = [
            ...($current->parent ? [$presenter->categoryLink($current->parent)] : []),
            ['name' => $current->name, 'url' => null],
        ];

        return $renderer->render(new CategoryPageData(
            $presenter->shell(),
            $presenter->seo($current->name.' | '.Store::current()->name, $current->name),
            category: ['name' => $current->name, 'slug' => $current->slug],
            products: $presenter->cards($products),
            sort: $sort,
            breadcrumbs: $presenter->breadcrumbs($trail),
        ));
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function sorted(Builder $query, string $sort): void
    {
        match ($sort) {
            'price_asc' => $query->orderByRaw('coalesce(sale_price, base_price) asc')->orderBy('id'),
            'price_desc' => $query->orderByRaw('coalesce(sale_price, base_price) desc')->orderBy('id'),
            default => $query->latest('id'),
        };
    }
}
