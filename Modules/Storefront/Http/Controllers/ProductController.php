<?php

namespace Modules\Storefront\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductMedia;
use Modules\Catalog\Models\Variant;
use Modules\Storefront\Support\StorefrontPresenter;
use Modules\Tenancy\Models\Store;

class ProductController
{
    public function __invoke(StorefrontPresenter $presenter, string $store, string $product): Response
    {
        $current = Product::query()
            ->published()
            ->with(['media', 'variants' => fn ($variants) => $variants->orderBy('id'), 'category.parent'])
            ->where('slug', $product)
            ->firstOrFail();

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

        return Inertia::render('storefront/Product', [
            ...$presenter->shell(Store::current()),
            'product' => [
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
            'breadcrumbs' => $presenter->breadcrumbs($trail),
            'seo' => [
                'title' => $current->name.' | '.Store::current()->name,
                'description' => $presenter->metaDescription($current->description) ?: $current->name,
                'image' => $current->media->first()?->url('large'),
            ],
        ]);
    }
}
