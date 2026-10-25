<?php

namespace Modules\Tenancy\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Modules\Tenancy\Models\Store;
use Modules\Tenancy\Rules\StoreSlug;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreSlugTest extends TestCase
{
    use RefreshDatabase;

    private function passes(string $slug, ?int $ignoreStoreId = null): bool
    {
        return Validator::make(['slug' => $slug], ['slug' => [new StoreSlug($ignoreStoreId)]])->passes();
    }

    public function test_a_valid_slug_passes(): void
    {
        $this->assertTrue($this->passes('noor-hijab'));
        $this->assertTrue($this->passes('abc'));
        $this->assertTrue($this->passes('shop-123'));
        $this->assertTrue($this->passes(str_repeat('a', 30)));
    }

    public function test_length_must_be_three_to_thirty_characters(): void
    {
        $this->assertFalse($this->passes('ab'));
        $this->assertFalse($this->passes(str_repeat('a', 31)));
    }

    #[DataProvider('invalidCharacters')]
    public function test_only_lowercase_letters_digits_and_hyphens_are_allowed(string $slug): void
    {
        $this->assertFalse($this->passes($slug));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidCharacters(): array
    {
        return [
            'uppercase' => ['NoorHijab'],
            'underscore' => ['noor_hijab'],
            'space' => ['noor hijab'],
            'dot' => ['noor.hijab'],
            'slash' => ['noor/hijab'],
            'arabic' => ['متجر-نور'],
        ];
    }

    public function test_a_slug_must_be_unique(): void
    {
        $store = Store::factory()->create(['slug' => 'taken-slug']);

        $this->assertFalse($this->passes('taken-slug'));
        $this->assertTrue($this->passes('taken-slug', $store->id), 'a store may keep its own slug');
    }

    public function test_every_reserved_slug_is_rejected(): void
    {
        $reserved = config('tenancy.reserved_slugs');

        $this->assertContains('admin', $reserved);

        foreach ($reserved as $slug) {
            $this->assertFalse($this->passes($slug), "Reserved slug [{$slug}] must be rejected.");
        }
    }

    public function test_the_reserved_list_comes_from_configuration(): void
    {
        $this->assertTrue($this->passes('promo-page'));

        config(['tenancy.reserved_slugs' => ['promo-page']]);

        $this->assertFalse($this->passes('promo-page'));
    }

    public function test_the_reserved_list_covers_the_platform_routes(): void
    {
        $expected = ['admin', 'vendor', 'api', 'login', 'register', 'logout', 'storage', 'assets', 'build', 'up', 'livewire', 'filament', 'invitations', 'theme-previews', 'payment-receipts'];

        $this->assertEqualsCanonicalizing($expected, config('tenancy.reserved_slugs'));
    }

    public function test_a_reserved_slug_url_never_opens_a_storefront(): void
    {
        // Inserted directly, bypassing validation, to prove the route itself refuses it.
        Store::query()->forceCreate(['slug' => 'build', 'name' => 'Sneaky', 'status' => 'active']);

        $this->get('/build')->assertNotFound();
    }

    public function test_the_admin_address_is_never_treated_as_a_store(): void
    {
        Store::query()->forceCreate(['slug' => 'admin', 'name' => 'Sneaky', 'status' => 'active']);

        $this->get('/admin')->assertRedirect();
    }
}
