<?php

namespace Modules\Backoffice\Policies;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Backoffice\Models\BlogPost;

/**
 * Blog posts are platform content: only a Platform Admin manages them, so
 * any other actor (a Customer or a Vendor Owner) is denied outright.
 */
class BlogPostPolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->isPlatformAdmin();
    }

    public function view(Authenticatable $actor, BlogPost $post): bool
    {
        return $actor instanceof User && $actor->isPlatformAdmin();
    }

    public function create(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->isPlatformAdmin();
    }

    public function update(Authenticatable $actor, BlogPost $post): bool
    {
        return $actor instanceof User && $actor->isPlatformAdmin();
    }

    public function delete(Authenticatable $actor, BlogPost $post): bool
    {
        return $actor instanceof User && $actor->isPlatformAdmin();
    }

    public function deleteAny(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->isPlatformAdmin();
    }

    public function restore(Authenticatable $actor, BlogPost $post): bool
    {
        return $actor instanceof User && $actor->isPlatformAdmin();
    }

    public function restoreAny(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->isPlatformAdmin();
    }

    public function forceDelete(Authenticatable $actor, BlogPost $post): bool
    {
        return $actor instanceof User && $actor->isPlatformAdmin();
    }

    public function forceDeleteAny(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->isPlatformAdmin();
    }

    public function reorder(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->isPlatformAdmin();
    }

    public function replicate(Authenticatable $actor, BlogPost $post): bool
    {
        return $actor instanceof User && $actor->isPlatformAdmin();
    }
}
