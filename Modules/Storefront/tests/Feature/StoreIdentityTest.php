<?php

namespace Modules\Storefront\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class StoreIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_storefront_wears_the_stores_identity(): void
    {
        Storage::fake('public');

        Store::factory()->create([
            'slug' => 'noor-hijab',
            'name' => 'Noor Hijab',
            'primary_color' => '#1B2A49',
            'secondary_color' => '#F3E8EE',
            'logo_path' => 'stores/1/logo.png',
            'favicon_path' => 'stores/1/icon.png',
            'footer_text' => 'صنع بحب',
            'contact_email' => 'hello@noor.test',
            'contact_phone' => '+201012345678',
            'contact_whatsapp' => '+201012345678',
            'social_links' => ['instagram' => 'https://instagram.com/noor'],
        ]);

        $this->get('/noor-hijab')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('tokens.--color-primary', '#1B2A49')
                ->where('tokens.--color-secondary', '#F3E8EE')
                ->where('store.footerText', 'صنع بحب')
                ->where('store.contact.email', 'hello@noor.test')
                ->where('store.contact.whatsapp', '+201012345678')
                ->where('store.social.instagram', 'https://instagram.com/noor')
                ->where('store.logoUrl', Storage::disk('public')->url('stores/1/logo.png'))
                ->where('store.faviconUrl', Storage::disk('public')->url('stores/1/icon.png')));
    }

    public function test_text_on_the_primary_colour_is_white_or_black_for_legibility(): void
    {
        Store::factory()->create(['slug' => 'dark-shop', 'primary_color' => '#1B2A49']);
        Store::factory()->create(['slug' => 'light-shop', 'primary_color' => '#FFD700']);

        $this->get('/dark-shop')->assertInertia(fn (Assert $page) => $page->where('tokens.--color-on-primary', '#FFFFFF'));
        $this->get('/light-shop')->assertInertia(fn (Assert $page) => $page->where('tokens.--color-on-primary', '#000000'));
    }

    public function test_a_store_without_its_own_colours_keeps_the_defaults(): void
    {
        Store::factory()->create(['slug' => 'plain-shop']);

        $this->get('/plain-shop')->assertInertia(fn (Assert $page) => $page
            ->where('tokens.--color-primary', config('storefront.tokens.--color-primary'))
            ->where('store.logoUrl', null)
            ->where('store.footerText', null));
    }

    public function test_one_stores_identity_never_leaks_into_another(): void
    {
        Store::factory()->create(['slug' => 'shop-a', 'primary_color' => '#1B2A49', 'footer_text' => 'A only']);
        Store::factory()->create(['slug' => 'shop-b']);

        $this->get('/shop-b')->assertInertia(fn (Assert $page) => $page
            ->where('tokens.--color-primary', config('storefront.tokens.--color-primary'))
            ->where('store.footerText', null));
    }
}
