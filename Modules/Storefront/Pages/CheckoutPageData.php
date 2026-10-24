<?php

namespace Modules\Storefront\Pages;

final class CheckoutPageData extends PageData
{
    /**
     * @param  array<string, mixed>  $shell
     * @param  array{title: string, description: string|null, image: string|null}  $seo
     */
    public function __construct(
        array $shell,
        array $seo,
        public readonly bool $ready,
    ) {
        parent::__construct($shell, $seo);
    }

    public static function page(): string
    {
        return 'Checkout';
    }

    protected function pageProps(): array
    {
        return [
            'ready' => $this->ready,
        ];
    }
}
