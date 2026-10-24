<?php

namespace Modules\Storefront\Pages;

final class OrdersPageData extends PageData
{
    /**
     * @param  array<string, mixed>  $shell
     * @param  array{title: string, description: string|null, image: string|null}  $seo
     * @param  list<array{number: string, status: string, total: string}>  $orders
     */
    public function __construct(
        array $shell,
        array $seo,
        public readonly array $orders,
    ) {
        parent::__construct($shell, $seo);
    }

    public static function page(): string
    {
        return 'Orders';
    }

    protected function pageProps(): array
    {
        return [
            'orders' => $this->orders,
        ];
    }
}
