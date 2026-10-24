<?php

namespace Modules\Tenancy\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Tenancy\Enums\StoreStatus;
use Modules\Tenancy\Models\Store;
use Symfony\Component\HttpFoundation\Response;

/**
 * Draft Stores look non-existent to the public (their own Vendor Owner and
 * Platform Admins may preview); Suspended Stores show a maintenance page.
 */
class EnsureStoreIsPublic
{
    public function handle(Request $request, Closure $next): Response
    {
        $store = Store::current();

        return match ($store?->status) {
            StoreStatus::Active => $next($request),
            StoreStatus::Draft => $this->canPreview($store) ? $next($request) : response()->view('tenancy::not-found', [], 404),
            StoreStatus::Suspended => response()->view('tenancy::maintenance', ['store' => $store], 503, ['Retry-After' => 3600]),
            default => response()->view('tenancy::not-found', [], 404),
        };
    }

    private function canPreview(Store $store): bool
    {
        $user = Auth::guard('web')->user();

        return $user instanceof User
            && ($user->isPlatformAdmin() || ($user->isVendorOwner() && $user->store_id === $store->id));
    }
}
