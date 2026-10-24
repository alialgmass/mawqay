<?php

namespace Modules\Auth\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Auth\Models\Address;
use Modules\Auth\Models\Customer;

/**
 * A Customer touches only their own addresses; no one else touches any.
 */
class AddressPolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        return $actor instanceof Customer;
    }

    public function create(Authenticatable $actor): bool
    {
        return $actor instanceof Customer;
    }

    public function view(Authenticatable $actor, Address $address): bool
    {
        return $this->owns($actor, $address);
    }

    public function update(Authenticatable $actor, Address $address): bool
    {
        return $this->owns($actor, $address);
    }

    public function delete(Authenticatable $actor, Address $address): bool
    {
        return $this->owns($actor, $address);
    }

    private function owns(Authenticatable $actor, Address $address): bool
    {
        return $actor instanceof Customer
            && $actor->getKey() === $address->customer_id
            && $actor->store_id === $address->store_id;
    }
}
