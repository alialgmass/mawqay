<?php

namespace Modules\Core\Support;

use Closure;

/**
 * Holds which Store the current request, job or console run is working in.
 */
class StoreContext
{
    private ?int $storeId = null;

    private int $unscopedDepth = 0;

    public function id(): ?int
    {
        return $this->storeId;
    }

    public function set(?int $storeId): void
    {
        $this->storeId = $storeId;
    }

    public function forget(): void
    {
        $this->storeId = null;
    }

    /**
     * Run a callback inside a Store, restoring the previous Store afterwards.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function runAs(int $storeId, Closure $callback): mixed
    {
        $previous = $this->storeId;
        $this->storeId = $storeId;

        try {
            return $callback();
        } finally {
            $this->storeId = $previous;
        }
    }

    /**
     * Whether the Store scope is currently switched off. Only the AdminScope
     * helper may switch it off.
     *
     * @internal
     */
    public function isUnscoped(): bool
    {
        return $this->unscopedDepth > 0;
    }

    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     *
     * @internal
     */
    public function runUnscoped(Closure $callback): mixed
    {
        $this->unscopedDepth++;

        try {
            return $callback();
        } finally {
            $this->unscopedDepth--;
        }
    }
}
