<?php

namespace Modules\Tenancy\Actions;

use Modules\Tenancy\Enums\StoreStatus;
use Modules\Tenancy\Models\Store;
use RuntimeException;

class ChangeStoreStatus
{
    public function __invoke(Store $store, StoreStatus $next): Store
    {
        if (! $store->status->canMoveTo($next)) {
            throw new RuntimeException("A {$store->status->value} Store cannot become {$next->value}.");
        }

        $store->update(['status' => $next]);

        return $store;
    }
}
