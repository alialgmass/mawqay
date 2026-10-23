<?php

namespace Modules\Core\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Scopes\StoreScope;

/**
 * The only sanctioned way to look across Stores. Use it from Admin Panel code
 * only; an architecture test fails if it appears anywhere else.
 */
final class AdminScope
{
    /**
     * A query on the model that ignores the Store filter.
     *
     * @param  class-string<Model>  $model
     * @return Builder<Model>
     */
    public static function on(string $model): Builder
    {
        return $model::query()->withoutGlobalScope(StoreScope::class);
    }

    /**
     * Run a callback with the Store filter switched off.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public static function run(Closure $callback): mixed
    {
        return app(StoreContext::class)->runUnscoped($callback);
    }
}
