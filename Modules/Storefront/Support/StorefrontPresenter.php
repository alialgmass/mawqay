<?php

namespace Modules\Storefront\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Sales\Services\CartService;
use Modules\Tenancy\Models\Store;
use Modules\Theming\Support\ActiveTheme;
use Modules\Theming\Support\ThemeManager;

/**
 * Turns models into the plain arrays the Storefront pages receive, so pages
 * never see an Eloquent model. It remembers the Theme for the request it is asked about.
 */
class StorefrontPresenter
{
    private ?ActiveTheme $theme = null;

    private ?Request $themeRequest = null;

    public function __construct(private readonly ThemeManager $themes) {}

    /**
     * The Theme and settings this request renders with (a Vendor Owner's
     * preview included).
     */
    public function activeTheme(): ActiveTheme
    {
        if ($this->theme === null || $this->themeRequest !== request()) {
            $this->themeRequest = request();
            $this->theme = $this->themes->activeFor($this->store(), $this->themeRequest);
        }

        return $this->theme;
    }

    /**
     * What every Storefront page needs: the Store's identity, design tokens,
     * navigation and URLs.
     *
     * @return array<string, mixed>
     */
    public function shell(): array
    {
        $store = $this->store();
        $customer = Auth::guard('customer')->user();
        $slug = $store->slug;

        return [
            'store' => [
                'name' => $store->name,
                'slug' => $slug,
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
            'tokens' => $this->activeTheme()->styleVariables(),
            'urls' => [
                'home' => route('storefront.home', ['store' => $slug]),
                'search' => route('storefront.search', ['store' => $slug]),
                'cart' => url("/{$slug}/cart"),
                'checkout' => url("/{$slug}/checkout"),
                'orders' => url("/{$slug}/orders"),
                'login' => url("/{$slug}/login"),
                'register' => url("/{$slug}/register"),
                'forgotPassword' => url("/{$slug}/forgot-password"),
                'account' => url("/{$slug}/account"),
                'addresses' => url("/{$slug}/account/addresses"),
            ],
            'actions' => [
                'login' => url("/{$slug}/customer/login"),
                'register' => url("/{$slug}/customer/register"),
                'logout' => url("/{$slug}/customer/logout"),
                'forgotPassword' => url("/{$slug}/customer/forgot-password"),
                'resetPassword' => url("/{$slug}/customer/reset-password"),
                'profile' => url("/{$slug}/customer/profile"),
                'password' => url("/{$slug}/customer/password"),
                'addresses' => url("/{$slug}/customer/addresses"),
                'cartItems' => url("/{$slug}/cart/items"),
                'acceptPrices' => url("/{$slug}/cart/accept-prices"),
                'checkout' => url("/{$slug}/checkout"),
            ],
            'status' => session('status'),
            'navigation' => Category::query()->shown()->topLevel()->orderBy('sort')->orderBy('id')->get()
                ->map(fn (Category $category): array => $this->categoryLink($category))->all(),
            'cartCount' => app(CartService::class)->count(),
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
     * @return array{title: string, description: string|null, image: string|null}
     */
    public function seo(string $title, ?string $description = null, ?string $image = null): array
    {
        return ['title' => $title, 'description' => $description, 'image' => $image];
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

    private function store(): Store
    {
        return Store::current();
    }

    private function storeSlug(): string
    {
        return $this->store()->slug;
    }
}
