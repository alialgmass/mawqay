<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Auth\Filament\Admin\Resources\Customers\CustomerResource;
use Modules\Auth\Filament\Admin\Resources\Customers\Pages\ListCustomers as AdminListCustomers;
use Modules\Auth\Filament\Admin\Resources\Customers\Pages\ViewCustomer as AdminViewCustomer;
use Modules\Auth\Filament\Admin\Resources\Customers\RelationManagers\AddressesRelationManager;
use Modules\Auth\Filament\Vendor\Resources\Customers\Pages\ListCustomers as VendorListCustomers;
use Modules\Auth\Filament\Vendor\Resources\Customers\Pages\ViewCustomer as VendorViewCustomer;
use Modules\Auth\Models\Address;
use Modules\Auth\Models\Customer;
use Modules\Core\Support\StoreContext;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class CustomerPanelsTest extends TestCase
{
    use RefreshDatabase;

    private Store $storeA;

    private Store $storeB;

    private Customer $mona;

    private Customer $layla;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = Store::factory()->create(['slug' => 'store-a', 'name' => 'Alpha']);
        $this->storeB = Store::factory()->create(['slug' => 'store-b', 'name' => 'Beta']);
        $this->mona = Customer::factory()->create(['store_id' => $this->storeA->id, 'name' => 'Mona', 'email' => 'mona@example.com', 'phone' => '01012345678']);
        $this->layla = Customer::factory()->create(['store_id' => $this->storeB->id, 'name' => 'Layla', 'email' => 'layla@example.com']);
    }

    private function actAsAdmin(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel('admin');
    }

    private function actAsOwnerOfA(): void
    {
        $this->actingAs(User::factory()->vendorOwner($this->storeA)->create());
        Filament::setCurrentPanel('vendor');
        Filament::setTenant($this->storeA);
        app(StoreContext::class)->set($this->storeA->id);
    }

    public function test_the_admin_sees_customers_of_every_store_with_filter_and_search(): void
    {
        $this->actAsAdmin();

        Livewire::test(AdminListCustomers::class)
            ->assertCanSeeTableRecords([$this->mona, $this->layla])
            ->assertTableColumnStateSet('store.name', 'Alpha', $this->mona)
            ->filterTable('store_id', $this->storeA->id)
            ->assertCanSeeTableRecords([$this->mona])
            ->assertCanNotSeeTableRecords([$this->layla]);
    }

    public function test_the_admin_searches_by_email(): void
    {
        $this->actAsAdmin();
        Livewire::test(AdminListCustomers::class)->searchTable('layla@')->assertCanSeeTableRecords([$this->layla])->assertCanNotSeeTableRecords([$this->mona]);
    }

    public function test_the_admin_searches_by_name(): void
    {
        $this->actAsAdmin();
        Livewire::test(AdminListCustomers::class)->searchTable('Mona')->assertCanSeeTableRecords([$this->mona])->assertCanNotSeeTableRecords([$this->layla]);
    }

    public function test_the_admin_searches_by_mobile_number(): void
    {
        $this->actAsAdmin();
        Livewire::test(AdminListCustomers::class)->searchTable('01012345678')->assertCanSeeTableRecords([$this->mona])->assertCanNotSeeTableRecords([$this->layla]);
    }

    public function test_the_admin_filters_by_active_state(): void
    {
        $this->actAsAdmin();
        $this->layla->update(['is_active' => false]);

        Livewire::test(AdminListCustomers::class)
            ->filterTable('is_active', '0')
            ->assertCanSeeTableRecords([$this->layla])
            ->assertCanNotSeeTableRecords([$this->mona]);
    }

    public function test_the_admin_views_a_customer_with_their_addresses(): void
    {
        $this->actAsAdmin();
        $address = Address::factory()->forCustomer($this->mona)->default()->create(['label' => 'Home']);
        Address::factory()->forCustomer($this->layla)->create(['label' => 'Elsewhere']);

        Livewire::test(AdminViewCustomer::class, ['record' => $this->mona->getKey()])->assertSee('Mona')->assertSee('mona@example.com');

        Livewire::test(AddressesRelationManager::class, ['ownerRecord' => $this->mona, 'pageClass' => AdminViewCustomer::class])
            ->assertCanSeeTableRecords([$address])
            ->assertCountTableRecords(1);
    }

    public function test_the_admin_deactivates_and_reactivates_a_customer(): void
    {
        $this->actAsAdmin();

        Livewire::test(AdminListCustomers::class)->callAction(TestAction::make('deactivate')->table($this->mona));
        $this->assertFalse($this->mona->fresh()->is_active);

        Livewire::test(AdminListCustomers::class)->callAction(TestAction::make('activate')->table($this->mona));
        $this->assertTrue($this->mona->fresh()->is_active);
    }

    public function test_a_deactivated_customer_is_locked_out(): void
    {
        $this->actAsAdmin();
        Livewire::test(AdminListCustomers::class)->callAction(TestAction::make('deactivate')->table($this->mona));

        $this->postJson('/store-a/customer/login', ['identifier' => 'mona@example.com', 'password' => 'password'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.identifier.0', __('auth::customers.deactivated'));
    }

    public function test_the_admin_cannot_edit_create_or_delete_customers(): void
    {
        $this->actAsAdmin();

        $this->assertFalse(CustomerResource::canCreate());
        $this->assertFalse(CustomerResource::canEdit($this->mona));
        $this->assertFalse(CustomerResource::canDelete($this->mona));
        $this->get("/admin/customers/{$this->mona->id}/edit")->assertNotFound();
    }

    public function test_only_platform_admins_open_the_admin_customer_list(): void
    {
        $this->actingAs(User::factory()->vendorOwner($this->storeA)->create());

        $this->get('/admin/customers')->assertForbidden();
    }

    public function test_the_vendor_sees_only_their_own_stores_customers_with_limited_columns(): void
    {
        $this->actAsOwnerOfA();
        Address::factory()->forCustomer($this->mona)->default()->create(['city' => 'Dokki']);

        Livewire::test(VendorListCustomers::class)
            ->assertCanSeeTableRecords([$this->mona])
            ->assertCanNotSeeTableRecords([$this->layla])
            ->assertTableColumnStateSet('defaultAddress.city', 'Dokki', $this->mona)
            ->assertTableColumnExists('name')
            ->assertTableColumnExists('created_at')
            ->assertTableColumnDoesNotExist('phone')
            ->assertTableColumnDoesNotExist('email');
    }

    public function test_the_vendor_view_page_hides_the_email_and_has_no_export_or_edit(): void
    {
        $this->actAsOwnerOfA();

        Livewire::test(VendorViewCustomer::class, ['record' => $this->mona->getKey()])
            ->assertSee('Mona')
            ->assertDontSee('01012345678')
            ->assertDontSee('mona@example.com');

        $this->assertFalse(\Modules\Auth\Filament\Vendor\Resources\Customers\CustomerResource::canCreate());
        $this->assertFalse(\Modules\Auth\Filament\Vendor\Resources\Customers\CustomerResource::canEdit($this->mona));
        $this->assertFalse(\Modules\Auth\Filament\Vendor\Resources\Customers\CustomerResource::canDelete($this->mona));
    }

    public function test_a_vendor_cannot_open_another_stores_customer(): void
    {
        $this->actingAs(User::factory()->vendorOwner($this->storeA)->create());

        $this->assertContains($this->get("/vendor/store-a/customers/{$this->layla->id}")->getStatusCode(), [403, 404]);
        $this->assertContains($this->get("/vendor/store-b/customers/{$this->layla->id}")->getStatusCode(), [403, 404]);
    }

    public function test_a_platform_admin_or_customer_cannot_open_the_vendor_list(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->get('/vendor/store-a/customers')->assertForbidden();

        $this->actingAs($this->mona, 'customer');
        $this->assertNotSame(200, $this->get('/vendor/store-a/customers')->getStatusCode());
    }
}
