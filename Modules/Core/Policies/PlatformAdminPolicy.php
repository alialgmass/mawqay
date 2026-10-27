<?php

namespace Modules\Core\Policies;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Platform-wide records that only a Platform Admin may see or change.
 */
abstract class PlatformAdminPolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        return $this->isAdmin($actor);
    }

    public function view(Authenticatable $actor, Model $record): bool
    {
        return $this->isAdmin($actor);
    }

    public function create(Authenticatable $actor): bool
    {
        return $this->isAdmin($actor);
    }

    public function update(Authenticatable $actor, Model $record): bool
    {
        return $this->isAdmin($actor);
    }

    public function delete(Authenticatable $actor, Model $record): bool
    {
        return $this->isAdmin($actor);
    }

    public function deleteAny(Authenticatable $actor): bool
    {
        return $this->isAdmin($actor);
    }

    public function restore(Authenticatable $actor, Model $record): bool
    {
        return $this->isAdmin($actor);
    }

    public function restoreAny(Authenticatable $actor): bool
    {
        return $this->isAdmin($actor);
    }

    public function forceDelete(Authenticatable $actor, Model $record): bool
    {
        return $this->isAdmin($actor);
    }

    public function forceDeleteAny(Authenticatable $actor): bool
    {
        return $this->isAdmin($actor);
    }

    public function reorder(Authenticatable $actor): bool
    {
        return $this->isAdmin($actor);
    }

    public function replicate(Authenticatable $actor, Model $record): bool
    {
        return $this->isAdmin($actor);
    }

    private function isAdmin(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->isPlatformAdmin();
    }
}
