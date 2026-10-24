<?php

namespace Modules\Storefront\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Core\Support\ColorContrast;
use Modules\Tenancy\Models\Store;

/**
 * Turns models into the plain arrays the Storefront pages receive, so pages
 * never see an Eloquent model.
 */
class StorefrontPresenter
{
    /**
     * What every Storefront page needs: the Store's identity and design tokens.
     *
     * @return array<string, mixed>
     */
    public function shell(Store $store): array
    {
        $customer = Auth::guard('customer')->user();

        return [
            'store' => [
                'name' => $store->name,
                'slug' => $store->slug,
                'logoUrl' => $store->logoUrl(),
                'faviconUrl' => $store->faviconUrl(),
                'footerText' => $store->footer_text,
                'contact' => [
                    'email' => $store->contact_email,
                    'phone' => $store->contact_phone,
                    'whatsapp' => $store->contact_whatsapp,
                ],
                'social' => $store->social_links ?? [],
            ],
            'customer' => $customer ? ['name' => $customer->name] : null,
            'tokens' => $this->tokensFor($store),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function card(Product $product): array
    {
        $cover = $product->media->first();

        return [
            'name' => $product->name,
            'url' => route('storefront.product', ['store' => $this->storeSlug(), 'product' => $product->slug]),
            'price' => $product->currentPrice()->format(),
            'originalPrice' => $product->isOnSale() ? $product->basePrice()->format() : null,
            'imageUrl' => $cover?->url('medium'),
        ];
    }

    /**
     * @param  LengthAwarePaginator<int, Product>  $paginator
     * @return array<string, mixed>
     */
    public function cards(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => $paginator->getCollection()->map(fn (Product $product): array => $this->card($product))->values()->all(),
            'prevUrl' => $paginator->previousPageUrl(),
            'nextUrl' => $paginator->nextPageUrl(),
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * @return array{name: string, url: string}
     */
    public function categoryLink(Category $category): array
    {
        return [
            'name' => $category->name,
            'url' => route('storefront.category', ['store' => $this->storeSlug(), 'category' => $category->slug]),
        ];
    }

    /**
     * @param  list<array{name: string, url: string|null}>  $trail
     * @return list<array{name: string, url: string|null}>
     */
    public function breadcrumbs(array $trail): array
    {
        return [['name' => $this->store()->name, 'url' => route('storefront.home', ['store' => $this->storeSlug()])], ...$trail];
    }

    /**
     * A short plain-text version of the Product description for meta tags and display.
     */
    public function plainText(?string $html): string
    {
        $withBreaks = preg_replace('#</(p|li|h[1-6]|blockquote)>|<br\s*/?>#i', "\n", (string) $html);

        return trim(html_entity_decode(strip_tags((string) $withBreaks)));
    }

    public function metaDescription(?string $html): string
    {
        return Str::limit(preg_replace('/\s+/u', ' ', $this->plainText($html)) ?? '', 157);
    }

    /**
     * The default tokens with the Store's own colours laid over them, plus the
     * text colour that reads best on each.
     *
     * @return array<string, string>
     */
    private function tokensFor(Store $store): array
    {
        $tokens = config('storefront.tokens');

        if ($store->primary_color && ColorContrast::isHex($store->primary_color)) {
            $tokens['--color-primary'] = $store->primary_color;
        }

        if ($store->secondary_color && ColorContrast::isHex($store->secondary_color)) {
            $tokens['--color-secondary'] = $store->secondary_color;
        }

        $tokens['--color-on-primary'] = ColorContrast::readableTextOn($tokens['--color-primary']);
        $tokens['--color-on-secondary'] = ColorContrast::readableTextOn($tokens['--color-secondary']);

        return $tokens;
    }

    private function store(): Store
    {
        return Store::current();
    }

    private function storeSlug(): string
    {
        return $this->store()->slug;
    }
}
