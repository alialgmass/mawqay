<?php

namespace Modules\Storefront\Pages;

final class SearchPageData extends PageData
{
    /**
     * @param  array<string, mixed>  $shell
     * @param  array{title: string, description: string|null, image: string|null}  $seo
     * @param  array<string, mixed>  $products
     * @param  list<array{name: string, url: string|null}>  $breadcrumbs
     */
    public function __construct(
        array $shell,
        array $seo,
        public readonly string $query,
        public readonly array $products,
        public readonly array $breadcrumbs,
    ) {
        parent::__construct($shell, $seo);
    }

    public static function page(): string
    {
        return 'Search';
    }

    protected function pageProps(): array
    {
        return [
            'query' => $this->query,
            'products' => $this->products,
            'breadcrumbs' => $this->breadcrumbs,
        ];
    }
}
