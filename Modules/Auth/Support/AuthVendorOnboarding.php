<?php

namespace Modules\Auth\Support;

use Modules\Auth\Actions\CreateStoreWithOwner;
use Modules\Auth\Actions\ResendInvitation;
use Modules\Tenancy\Contracts\VendorOnboarding;
use Modules\Tenancy\Models\Store;

class AuthVendorOnboarding implements VendorOnboarding
{
    public function __construct(
        private readonly CreateStoreWithOwner $createStoreWithOwner,
        private readonly ResendInvitation $resendInvitation,
    ) {}

    public function createStoreWithOwner(array $data): Store
    {
        return ($this->createStoreWithOwner)($data);
    }

    public function resendInvitation(Store $store): void
    {
        ($this->resendInvitation)($store);
    }
}
