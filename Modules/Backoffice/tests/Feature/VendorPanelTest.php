<?php

namespace Modules\Backoffice\Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\Customer;
use Modules\Core\Support\StoreContext;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class VendorPanelTest extends TestCase
{
    use RefreshDatabase;

    private Store $storeA;

    private Store $storeB;

    private User $ownerA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = Store::factory()->create(['slug' => 'store-a']);
        $this->storeB = Store::factory()->create(['slug' => 'store-b']);
        $this->ownerA = User::factory()->vendorOwner($this->storeA)->create();
    }

    public function test_a_vendor_owner_lands_in_their_own_stores_panel(): void
    {
        $this->actingAs($this->ownerA)->get('/vendor')->assertRedirect('/vendor/store-a');

        $this->actingAs($this->ownerA)->get('/vendor/store-a')->assertOk();
    }

    public function test_there_is_no_store_switcher(): void
    {
        $this->assertFalse(Filament::getPanel('vendor')->hasTenantMenu());

        $this->actingAs($this->ownerA)
            ->get('/vendor/store-a')
            ->assertOk()
            ->assertDontSee('fi-tenant-menu');
    }

    public function test_another_stores_panel_address_is_refused(): void
    {
        $status = $this->actingAs($this->ownerA)->get('/vendor/store-b')->getStatusCode();

        $this->assertContains($status, [403, 404]);
    }

    public function test_a_vendor_owner_is_refused_at_the_admin_panel(): void
    {
        $this->actingAs($this->ownerA)->get('/admin')->assertForbidden();
    }

    public function test_a_platform_admin_is_refused_at_the_vendor_panel(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/vendor')->assertForbidden();
    }

    public function test_a_guest_is_sent_to_the_vendor_login_page(): void
    {
        $this->get('/vendor')->assertRedirect('/vendor/login');
    }

    public function test_a_customer_sign_in_does_not_grant_access(): void
    {
        $customer = Customer::factory()->for($this->storeA, 'store')->create();

        $this->actingAs($customer, 'customer')->get('/vendor')->assertRedirect('/vendor/login');
    }

    public function test_the_panel_is_arabic_and_right_to_left(): void
    {
        $this->get('/vendor/login')
            ->assertOk()
            ->assertSee('lang="ar"', false)
            ->assertSee('dir="rtl"', false);
    }

    public function test_the_store_scope_follows_the_vendor_owners_store_inside_the_panel(): void
    {
        $this->actingAs($this->ownerA)->get('/vendor/store-a')->assertOk();

        $this->assertSame($this->storeA->id, app(StoreContext::class)->id());
    }
}
