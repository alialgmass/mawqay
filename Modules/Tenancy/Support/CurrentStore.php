<?php

namespace Modules\Tenancy\Support;

use Modules\Tenancy\Models\Store;

class CurrentStore
{
    private ?Store $store = null;

    public function get(): ?Store
    {
        return $this->store;
    }

    public function set(Store $store): void
    {
        $this->store = $store;
    }

    public function forget(): void
    {
        $this->store = null;
    }
}
