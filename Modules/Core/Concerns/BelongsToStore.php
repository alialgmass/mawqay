<?php

namespace Modules\Core\Concerns;

use LogicException;
use Modules\Core\Scopes\StoreScope;
use Modules\Core\Support\StoreContext;

/**
 * Marks a model as owned by a Store: queries are filtered to the current Store
 * and new records are assigned to it.
 */
trait BelongsToStore
{
    public static function bootBelongsToStore(): void
    {
        static::addGlobalScope(new StoreScope);

        static::creating(function ($model): void {
            if ($model->getAttribute('store_id') !== null) {
                return;
            }

            $storeId = app(StoreContext::class)->id();

            if ($storeId === null) {
                throw new LogicException('Cannot create a Store-owned record outside a Store.');
            }

            $model->setAttribute('store_id', $storeId);
        });
    }
}
