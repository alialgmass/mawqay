<?php

namespace Modules\Storefront\Pages;

final class CartPageData extends PageData
{
    /**
     * @param  array<string, mixed>  $shell
     * @param  array{title: string, description: string|null, image: string|null}  $seo
     * @param  list<array{name: string, quantity: int, price: string}>  $items
     */
    public function __construct(
        array $shell,
        array $seo,
        public readonly array $items,
        public readonly ?string $total,
    ) {
        parent::__construct($shell, $seo);
    }

    public static function page(): string
    {
        return 'Cart';
    }

    protected function pageProps(): array
    {
        return [
            'items' => $this->items,
            'total' => $this->total,
        ];
    }
}
