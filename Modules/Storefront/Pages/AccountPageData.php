<?php

namespace Modules\Storefront\Pages;

final class AccountPageData extends PageData
{
    /**
     * @param  array<string, mixed>  $shell
     * @param  array{title: string, description: string|null, image: string|null}  $seo
     * @param  array{name: string}|null  $customer
     */
    public function __construct(
        array $shell,
        array $seo,
        public readonly ?array $customer,
    ) {
        parent::__construct($shell, $seo);
    }

    public static function page(): string
    {
        return 'Account';
    }

    protected function pageProps(): array
    {
        return [
            'customer' => $this->customer,
        ];
    }
}
