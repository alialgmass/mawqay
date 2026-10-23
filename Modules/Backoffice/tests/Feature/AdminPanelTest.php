<?php

namespace Modules\Backoffice\Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Tenancy\Filament\Admin\Resources\Stores\Pages\ListStores;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_platform_admin_reaches_the_admin_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_a_vendor_owner_is_refused_at_the_admin_panel(): void
    {
        $owner = User::factory()->vendorOwner(Store::factory()->create())->create();

        $this->actingAs($owner)->get('/admin')->assertForbidden();
    }

    public function test_a_user_with_no_role_is_refused_at_the_admin_panel(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_a_guest_is_sent_to_the_login_page(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_the_panel_is_arabic_and_right_to_left(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('lang="ar"', false)
            ->assertSee('dir="rtl"', false);
    }

    public function test_the_store_list_shows_every_store(): void
    {
        $admin = User::factory()->admin()->create();
        $stores = Store::factory()->count(3)->create();

        $this->actingAs($admin);

        Livewire::test(ListStores::class)->assertCanSeeTableRecords($stores);
    }

    public function test_a_store_can_be_viewed_read_only(): void
    {
        $admin = User::factory()->admin()->create();
        $store = Store::factory()->create(['name' => 'Noor Hijab']);

        $this->actingAs($admin)
            ->get("/admin/stores/{$store->id}")
            ->assertOk()
            ->assertSee('Noor Hijab');
    }

    public function test_stores_cannot_be_created_edited_or_deleted_here(): void
    {
        $admin = User::factory()->admin()->create();
        $store = Store::factory()->create();

        $this->actingAs($admin);

        $this->get('/admin/stores/create')->assertNotFound();
        $this->get("/admin/stores/{$store->id}/edit")->assertNotFound();
    }

    public function test_the_store_resource_arrives_through_a_module_plugin(): void
    {
        $this->assertTrue(Filament::getPanel('admin')->hasPlugin('tenancy'));
    }
}
