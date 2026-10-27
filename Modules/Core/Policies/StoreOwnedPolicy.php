<?php

namespace Modules\Core\Policies;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Who may work with a Store-owned record: a Platform Admin on any Store, a Vendor Owner only on their own
 * Store, and nobody else (Customers never reach the panels).
 */
abstract class StoreOwnedPolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        return $actor instanceof User && ($actor->isPlatformAdmin() || $actor->isVendorOwner());
    }

    public function view(Authenticatable $actor, Model $record): bool
    {
        return $this->owns($actor, $record);
    }

    public function create(Authenticatable $actor): bool
    {
        return $this->viewAny($actor);
    }

    public function update(Authenticatable $actor, Model $record): bool
    {
        return $this->owns($actor, $record);
    }

    public function delete(Authenticatable $actor, Model $record): bool
    {
        return $this->owns($actor, $record);
    }

    public function deleteAny(Authenticatable $actor): bool
    {
        return $this->viewAny($actor);
    }

    public function restore(Authenticatable $actor, Model $record): bool
    {
        return $this->owns($actor, $record);
    }

    public function restoreAny(Authenticatable $actor): bool
    {
        return $this->viewAny($actor);
    }

    public function forceDelete(Authenticatable $actor, Model $record): bool
    {
        return $this->owns($actor, $record);
    }

    public function forceDeleteAny(Authenticatable $actor): bool
    {
        return $this->viewAny($actor);
    }

    public function reorder(Authenticatable $actor): bool
    {
        return $this->viewAny($actor);
    }

    public function replicate(Authenticatable $actor, Model $record): bool
    {
        return $this->owns($actor, $record);
    }

    private function owns(Authenticatable $actor, Model $record): bool
    {
        if (! $actor instanceof User) {
            return false;
        }

        return $actor->isPlatformAdmin()
            || ($actor->isVendorOwner() && (int) $actor->store_id === (int) $record->getAttribute('store_id'));
    }
}
