<?php

namespace Modules\Tenancy\Contracts;

use Modules\Tenancy\Models\Store;

/**
 * Onboarding of a Vendor, fulfilled by the Auth module so Tenancy never
 * depends on it directly.
 */
interface VendorOnboarding
{
    /**
     * Creates a Draft Store with its Vendor Owner and sends the invitation.
     *
     * @param  array<string, mixed>  $data
     */
    public function createStoreWithOwner(array $data): Store;

    /**
     * Sends the Store's owner a fresh invitation, retiring the earlier one.
     */
    public function resendInvitation(Store $store): void;
}
