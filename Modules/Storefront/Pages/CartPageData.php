<?php

namespace Modules\Storefront\Pages;

final class CartPageData extends PageData
{
    /**
     * @param  array<string, mixed>  $shell
     * @param  array{title: string, description: string|null, image: string|null}  $seo
     * @param  list<array<string, mixed>>  $items
     */
    public function __construct(
        array $shell,
        array $seo,
        public readonly array $items,
        public readonly string $total,
        public readonly bool $canCheckout,
        public readonly bool $hasChanges,
        public readonly bool $storeOpen,
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
            'canCheckout' => $this->canCheckout,
            'hasChanges' => $this->hasChanges,
            'storeOpen' => $this->storeOpen,
        ];
    }
}
