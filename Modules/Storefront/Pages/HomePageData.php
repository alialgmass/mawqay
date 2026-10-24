<?php

namespace Modules\Storefront\Pages;

final class HomePageData extends PageData
{
    /**
     * @param  array<string, mixed>  $shell
     * @param  array{title: string, description: string|null, image: string|null}  $seo
     * @param  list<array<string, mixed>>  $latest
     * @param  list<array{name: string, url: string}>  $categories
     */
    public function __construct(
        array $shell,
        array $seo,
        public readonly array $latest,
        public readonly array $categories,
    ) {
        parent::__construct($shell, $seo);
    }

    public static function page(): string
    {
        return 'Home';
    }

    protected function pageProps(): array
    {
        return [
            'latest' => $this->latest,
            'categories' => $this->categories,
        ];
    }
}
