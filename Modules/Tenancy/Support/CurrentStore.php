<?php

namespace Modules\Tenancy\Support;

use Modules\Core\Support\StoreContext;
use Modules\Tenancy\Models\Store;

class CurrentStore
{
    private ?Store $store = null;

    public function __construct(private readonly StoreContext $context) {}

    public function get(): ?Store
    {
        return $this->store;
    }

    public function set(Store $store): void
    {
        $this->store = $store;
        $this->context->set($store->id);
    }

    public function forget(): void
    {
        $this->store = null;
        $this->context->forget();
    }
}
