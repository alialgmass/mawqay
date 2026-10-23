<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\Customer;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Store $storeA;

    private Store $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = Store::factory()->create();
        $this->storeB = Store::factory()->create();
    }

    public function test_a_gate_tells_the_three_actors_apart(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->vendorOwner($this->storeA)->create();
        $customer = Customer::factory()->for($this->storeA, 'store')->create();

        $this->assertTrue(Gate::forUser($admin)->allows('platform-admin'));
        $this->assertFalse(Gate::forUser($admin)->allows('vendor-owner'));

        $this->assertTrue(Gate::forUser($owner)->allows('vendor-owner'));
        $this->assertFalse(Gate::forUser($owner)->allows('platform-admin'));

        $this->assertFalse(Gate::forUser($customer)->allows('platform-admin'));
        $this->assertFalse(Gate::forUser($customer)->allows('vendor-owner'));
    }

    public function test_a_platform_admin_can_see_and_manage_every_store(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Store::class));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $this->storeA));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $this->storeB));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $this->storeA));
    }

    public function test_a_vendor_owner_can_view_only_their_own_store(): void
    {
        $owner = User::factory()->vendorOwner($this->storeA)->create();

        $this->assertTrue(Gate::forUser($owner)->allows('view', $this->storeA));
        $this->assertFalse(Gate::forUser($owner)->allows('view', $this->storeB));
        $this->assertFalse(Gate::forUser($owner)->allows('viewAny', Store::class));
        $this->assertFalse(Gate::forUser($owner)->allows('update', $this->storeA));
        $this->assertFalse(Gate::forUser($owner)->allows('delete', $this->storeA));
    }

    public function test_a_customer_is_denied_platform_and_vendor_actions(): void
    {
        $customer = Customer::factory()->for($this->storeA, 'store')->create();

        $this->assertFalse(Gate::forUser($customer)->allows('view', $this->storeA));
        $this->assertFalse(Gate::forUser($customer)->allows('viewAny', Store::class));
        $this->assertFalse(Gate::forUser($customer)->allows('update', $this->storeA));
    }

    public function test_a_user_with_no_role_is_denied_everything(): void
    {
        $user = User::factory()->create();

        $this->assertFalse(Gate::forUser($user)->allows('platform-admin'));
        $this->assertFalse(Gate::forUser($user)->allows('vendor-owner'));
        $this->assertFalse(Gate::forUser($user)->allows('view', $this->storeA));
    }

    public function test_a_guest_is_denied(): void
    {
        $this->assertFalse(Gate::allows('platform-admin'));
        $this->assertFalse(Gate::allows('view', $this->storeA));
    }
}
