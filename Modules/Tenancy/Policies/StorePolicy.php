<?php

namespace Modules\Tenancy\Policies;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Tenancy\Models\Store;

/**
 * Only a User can hold a platform role, so any other actor (a Customer) is
 * denied before the role is even looked at.
 */
class StorePolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->isPlatformAdmin();
    }

    public function view(Authenticatable $actor, Store $store): bool
    {
        if (! $actor instanceof User) {
            return false;
        }

        if ($actor->isPlatformAdmin()) {
            return true;
        }

        return $actor->isVendorOwner() && $actor->store_id === $store->id;
    }

    public function create(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->isPlatformAdmin();
    }

    public function update(Authenticatable $actor, Store $store): bool
    {
        return $actor instanceof User && $actor->isPlatformAdmin();
    }

    public function delete(Authenticatable $actor, Store $store): bool
    {
        return $actor instanceof User && $actor->isPlatformAdmin();
    }
}
