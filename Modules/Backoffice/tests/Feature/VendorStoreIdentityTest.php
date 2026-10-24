<?php

namespace Modules\Backoffice\Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Backoffice\Filament\Vendor\Pages\StoreIdentity;
use Modules\Tenancy\Enums\StoreStatus;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class VendorStoreIdentityTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private Store $otherStore;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->store = Store::factory()->create(['slug' => 'store-a', 'name' => 'Store A', 'commission_rate' => 9]);
        $this->otherStore = Store::factory()->create(['slug' => 'store-b', 'name' => 'Store B']);
        $this->owner = User::factory()->vendorOwner($this->store)->create();

        $this->actingAs($this->owner);
        Filament::setCurrentPanel('vendor');
        Filament::setTenant($this->store);
    }

    public function test_the_owner_edits_their_stores_identity(): void
    {
        Livewire::test(StoreIdentity::class)
            ->assertFormSet(['name' => 'Store A'])
            ->fillForm([
                'name' => 'Noor Hijab',
                'primary_color' => '#7A3E65',
                'secondary_color' => '#F3E8EE',
                'contact_email' => 'hello@noor.test',
                'contact_phone' => '+201012345678',
                'contact_whatsapp' => '+201012345678',
                'social_links' => ['instagram' => 'https://instagram.com/noor'],
                'footer_text' => 'صنع بحب',
                'shipping_origin' => 'Cairo',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $store = $this->store->fresh();
        $this->assertSame('Noor Hijab', $store->name);
        $this->assertSame('#7A3E65', $store->primary_color);
        $this->assertSame('#F3E8EE', $store->secondary_color);
        $this->assertSame('hello@noor.test', $store->contact_email);
        $this->assertSame(['instagram' => 'https://instagram.com/noor'], $store->social_links);
        $this->assertSame('صنع بحب', $store->footer_text);
        $this->assertSame('Cairo', $store->shipping_origin);
    }

    public function test_logo_and_favicon_are_stored_in_the_stores_own_folder(): void
    {
        Livewire::test(StoreIdentity::class)
            ->fillForm([
                'logo_path' => UploadedFile::fake()->image('logo.png'),
                'favicon_path' => UploadedFile::fake()->image('icon.png', 64, 64),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $store = $this->store->fresh();

        $this->assertStringStartsWith("stores/{$store->id}/", $store->logo_path);
        $this->assertStringStartsWith("stores/{$store->id}/", $store->favicon_path);
        Storage::disk('public')->assertExists($store->logo_path);
        Storage::disk('public')->assertExists($store->favicon_path);
    }

    public function test_colours_must_be_hex(): void
    {
        foreach (['red', '#12', '7A3E65', '#GGGGGG'] as $bad) {
            Livewire::test(StoreIdentity::class)
                ->fillForm(['primary_color' => $bad])
                ->call('save')
                ->assertHasFormErrors(['primary_color']);
        }

        $this->assertNull($this->store->fresh()->primary_color);
    }

    public function test_a_colour_that_fades_into_the_page_shows_a_contrast_warning(): void
    {
        Livewire::test(StoreIdentity::class)
            ->fillForm(['primary_color' => '#FFD700'])
            ->assertSee(__('backoffice::panel.identity.contrast_warning'));

        Livewire::test(StoreIdentity::class)
            ->fillForm(['primary_color' => '#1B2A49'])
            ->assertDontSee(__('backoffice::panel.identity.contrast_warning'));
    }

    public function test_social_links_must_be_http_urls(): void
    {
        Livewire::test(StoreIdentity::class)
            ->fillForm(['social_links' => ['instagram' => 'javascript:alert(1)']])
            ->call('save')
            ->assertHasFormErrors(['social_links']);

        $this->assertNull($this->store->fresh()->social_links);
    }

    public function test_a_poor_colour_is_only_a_warning_and_still_saves(): void
    {
        Livewire::test(StoreIdentity::class)
            ->fillForm(['primary_color' => '#FFD700'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('#FFD700', $this->store->fresh()->primary_color);
    }

    public function test_the_owner_cannot_change_slug_status_or_commission(): void
    {
        Livewire::test(StoreIdentity::class)
            ->fillForm(['name' => 'Renamed'])
            ->set('data.slug', 'hijacked')
            ->set('data.status', 'suspended')
            ->set('data.commission_rate', 0)
            ->call('save');

        $store = $this->store->fresh();
        $this->assertSame('Renamed', $store->name);
        $this->assertSame('store-a', $store->slug);
        $this->assertSame(StoreStatus::Active, $store->status);
        $this->assertSame('9.00', $store->commission_rate);
    }

    public function test_saving_never_touches_another_store(): void
    {
        Livewire::test(StoreIdentity::class)
            ->fillForm(['name' => 'Renamed', 'footer_text' => 'Mine'])
            ->call('save');

        $other = $this->otherStore->fresh();
        $this->assertSame('Store B', $other->name);
        $this->assertNull($other->footer_text);
    }

    public function test_the_identity_page_of_another_store_is_out_of_reach(): void
    {
        $this->assertSame(200, $this->get('/vendor/store-a/store-identity')->getStatusCode());
        $this->assertContains($this->get('/vendor/store-b/store-identity')->getStatusCode(), [403, 404]);
    }

    public function test_a_platform_admin_cannot_use_the_vendor_identity_page(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/vendor/store-a/store-identity')
            ->assertForbidden();
    }

    public function test_the_dashboard_shows_the_status_and_a_link_to_the_public_store(): void
    {
        $this->get('/vendor/store-a')
            ->assertOk()
            ->assertSee(__('tenancy::stores.statuses.active'))
            ->assertSee(url('/store-a'), false);

        $this->store->update(['status' => StoreStatus::Suspended]);

        $this->get('/vendor/store-a')->assertSee(__('tenancy::stores.statuses.suspended'));
    }
}
