<?php

namespace Modules\Storefront\Http\Controllers;

use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductMedia;
use Modules\Catalog\Models\Variant;
use Modules\Storefront\Pages\ProductPageData;
use Modules\Storefront\Support\StorefrontPresenter;
use Modules\Storefront\Support\ThemeRenderer;
use Modules\Tenancy\Models\Store;
use Symfony\Component\HttpFoundation\Response;

class ProductController
{
    public function __invoke(StorefrontPresenter $presenter, ThemeRenderer $renderer, string $store, string $product): Response
    {
        $current = Product::query()
            ->published()
            ->with(['media', 'variants' => fn ($variants) => $variants->orderBy('id'), 'category.parent'])
            ->where('slug', $product)
            ->first();

        if ($current === null) {
            return $renderer->notFound();
        }

        $current->variants->each->setRelation('product', $current);

        $trail = [
            ...($current->category->parent ? [$presenter->categoryLink($current->category->parent)] : []),
            $presenter->categoryLink($current->category),
            ['name' => $current->name, 'url' => null],
        ];

        $images = $current->media->map(fn (ProductMedia $media): array => [
            'thumb' => $media->url('thumb'),
            'large' => $media->url('large'),
        ])->all();

        return $renderer->render(new ProductPageData(
            $presenter->shell(),
            $presenter->seo(
                $current->name.' | '.Store::current()->name,
                $presenter->metaDescription($current->description) ?: $current->name,
                $current->media->first()?->url('large'),
            ),
            product: [
                'name' => $current->name,
                'description' => $presenter->plainText($current->description),
                'price' => $current->currentPrice()->format(),
                'originalPrice' => $current->isOnSale() ? $current->basePrice()->format() : null,
                'images' => $images,
                'variants' => $current->variants->map(fn (Variant $variant): array => [
                    'id' => $variant->id,
                    'size' => $variant->size,
                    'color' => $variant->color,
                    'price' => $variant->price()->format(),
                    'available' => $variant->isAvailable(),
                ])->values()->all(),
            ],
            breadcrumbs: $presenter->breadcrumbs($trail),
        ));
    }
}
