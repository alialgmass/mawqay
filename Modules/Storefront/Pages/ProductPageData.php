<?php

namespace Modules\Storefront\Pages;

final class ProductPageData extends PageData
{
    /**
     * @param  array<string, mixed>  $shell
     * @param  array{title: string, description: string|null, image: string|null}  $seo
     * @param  array<string, mixed>  $product
     * @param  list<array{name: string, url: string|null}>  $breadcrumbs
     */
    public function __construct(
        array $shell,
        array $seo,
        public readonly array $product,
        public readonly array $breadcrumbs,
    ) {
        parent::__construct($shell, $seo);
    }

    public static function page(): string
    {
        return 'Product';
    }

    protected function pageProps(): array
    {
        return [
            'product' => $this->product,
            'breadcrumbs' => $this->breadcrumbs,
        ];
    }
}
