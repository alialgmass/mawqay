<?php

namespace Modules\Storefront\Pages;

final class OrderConfirmationPageData extends PageData
{
    /**
     * @param  array<string, mixed>  $shell
     * @param  array{title: string, description: string|null, image: string|null}  $seo
     * @param  array<string, mixed>  $order
     */
    public function __construct(
        array $shell,
        array $seo,
        public readonly array $order,
    ) {
        parent::__construct($shell, $seo);
    }

    public static function page(): string
    {
        return 'OrderConfirmation';
    }

    protected function pageProps(): array
    {
        return [
            'order' => $this->order,
        ];
    }
}
