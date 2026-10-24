<?php

namespace Modules\Storefront\Pages;

final class NotFoundPageData extends PageData
{
    /**
     * @param  array<string, mixed>  $shell
     * @param  array{title: string, description: string|null, image: string|null}  $seo
     */
    public function __construct(
        array $shell,
        array $seo,
    ) {
        parent::__construct($shell, $seo);
    }

    public static function page(): string
    {
        return 'NotFound';
    }

    protected function pageProps(): array
    {
        return [
        ];
    }
}
