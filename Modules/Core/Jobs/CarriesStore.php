<?php

namespace Modules\Core\Jobs;

use LogicException;
use Modules\Core\Jobs\Middleware\RunsInStoreContext;
use Modules\Core\Support\StoreContext;

/**
 * A queued job that remembers the Store it was created for and runs inside it,
 * even on a worker with no current Store. Call captureStore() in the constructor.
 */
trait CarriesStore
{
    public ?int $storeId = null;

    protected function captureStore(): void
    {
        $storeId = app(StoreContext::class)->id();

        if ($storeId === null) {
            throw new LogicException('A Store-owned job must be created inside a Store.');
        }

        $this->storeId = $storeId;
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [new RunsInStoreContext($this->storeId)];
    }
}
