<?php

namespace Modules\Core\Jobs\Middleware;

use Closure;
use Modules\Core\Support\StoreContext;

class RunsInStoreContext
{
    public function __construct(private readonly ?int $storeId) {}

    public function handle(object $job, Closure $next): mixed
    {
        $context = app(StoreContext::class);

        if ($this->storeId === null) {
            return $next($job);
        }

        return $context->runAs($this->storeId, fn () => $next($job));
    }
}
