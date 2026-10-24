<?php

namespace Modules\Storefront\Pages;

final class AddressesPageData extends PageData
{
    /**
     * @param  array<string, mixed>  $shell
     * @param  array{title: string, description: string|null, image: string|null}  $seo
     * @param  list<array<string, mixed>>  $addresses
     * @param  list<array{value: string, name: string}>  $governorates
     */
    public function __construct(
        array $shell,
        array $seo,
        public readonly array $addresses,
        public readonly array $governorates,
        public readonly int $maxAddresses,
    ) {
        parent::__construct($shell, $seo);
    }

    public static function page(): string
    {
        return 'Addresses';
    }

    protected function pageProps(): array
    {
        return [
            'addresses' => $this->addresses,
            'governorates' => $this->governorates,
            'maxAddresses' => $this->maxAddresses,
        ];
    }
}
