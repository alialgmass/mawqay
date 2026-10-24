<?php

namespace Modules\Tenancy\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Tenancy\Models\Store;
use Modules\Tenancy\Models\StoreSlugRedirect;
use Modules\Tenancy\Rules\StoreSlug;

/**
 * Platform Admin only. The old address keeps working as a permanent redirect.
 */
class ChangeStoreSlug
{
    public function __invoke(Store $store, string $newSlug): Store
    {
        if ($newSlug === $store->slug) {
            return $store;
        }

        Validator::make(['slug' => $newSlug], ['slug' => [new StoreSlug($store->id)]])->validate();

        return DB::transaction(function () use ($store, $newSlug): Store {
            StoreSlugRedirect::query()->create(['old_slug' => $store->slug, 'store_id' => $store->id]);

            $store->update(['slug' => $newSlug]);

            return $store;
        });
    }
}
