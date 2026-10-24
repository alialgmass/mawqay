<?php

namespace Modules\Auth\Actions;

use Modules\Auth\Models\Address;

class UpdateAddress
{
    public function __construct(private readonly SetDefaultAddress $setDefault) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Address $address, array $data): Address
    {
        $wantsDefault = (bool) ($data['is_default'] ?? false);

        $address->update(collect($data)->except('is_default')->all());

        if ($wantsDefault) {
            ($this->setDefault)($address);
        }

        return $address;
    }
}
