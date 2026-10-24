<?php

namespace Modules\Storefront\Pages;

final class CategoryPageData extends PageData
{
    /**
     * @param  array<string, mixed>  $shell
     * @param  array{title: string, description: string|null, image: string|null}  $seo
     * @param  array{name: string, slug: string}  $category
     * @param  array<string, mixed>  $products
     * @param  list<array{name: string, url: string|null}>  $breadcrumbs
     */
    public function __construct(
        array $shell,
        array $seo,
        public readonly array $category,
        public readonly array $products,
        public readonly string $sort,
        public readonly array $breadcrumbs,
    ) {
        parent::__construct($shell, $seo);
    }

    public static function page(): string
    {
        return 'Category';
    }

    protected function pageProps(): array
    {
        return [
            'category' => $this->category,
            'products' => $this->products,
            'sort' => $this->sort,
            'breadcrumbs' => $this->breadcrumbs,
        ];
    }
}
