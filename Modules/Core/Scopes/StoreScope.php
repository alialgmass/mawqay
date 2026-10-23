<?php

namespace Modules\Core\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Modules\Core\Support\StoreContext;

/**
 * Filters Store-owned records to the current Store. With no Store in context it
 * returns nothing rather than everything, so a missing context can never leak.
 */
class StoreScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(StoreContext::class);

        if ($context->isUnscoped()) {
            return;
        }

        $storeId = $context->id();

        if ($storeId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->qualifyColumn('store_id'), $storeId);
    }
}
