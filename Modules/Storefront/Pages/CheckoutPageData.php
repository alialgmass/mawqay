<?php

namespace Modules\Storefront\Pages;

final class CheckoutPageData extends PageData
{
    /**
     * @param  array<string, mixed>  $shell
     * @param  array{title: string, description: string|null, image: string|null}  $seo
     * @param  list<array<string, mixed>>  $items
     * @param  list<array<string, mixed>>  $addresses
     * @param  list<array{value: string, label: string}>  $paymentMethods
     * @param  list<array{value: string, name: string}>  $governorates
     * @param  list<string>  $issues
     */
    public function __construct(
        array $shell,
        array $seo,
        public readonly bool $ready,
        public readonly array $items,
        public readonly string $subtotal,
        public readonly array $addresses,
        public readonly array $paymentMethods,
        public readonly array $governorates,
        public readonly int $maxAddresses,
        public readonly array $issues,
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
            'items' => $this->items,
            'subtotal' => $this->subtotal,
            'addresses' => $this->addresses,
            'paymentMethods' => $this->paymentMethods,
            'governorates' => $this->governorates,
            'maxAddresses' => $this->maxAddresses,
            'issues' => $this->issues,
        ];
    }
}
