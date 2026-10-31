<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\Models\Invitation;
use Modules\Tenancy\Enums\StoreStatus;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class StoreRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'store_name' => 'Noor Wardrobe',
            'store_slug' => 'noor-wardrobe',
            'owner_name' => 'Noor',
            'owner_email' => 'noor@example.com',
            'owner_phone' => '0500000000',
        ], $overrides);
    }

    public function test_a_vendor_registers_a_store_and_receives_an_invitation(): void
    {
        Mail::fake();

        $response = $this->from('/')->post(route('register.store'), $this->payload());

        $response->assertRedirect(route('home'))->assertSessionHas('status');

        $store = Store::query()->where('slug', 'noor-wardrobe')->first();

        $this->assertNotNull($store, 'a Store must be created');
        $this->assertSame(StoreStatus::Draft, $store->status);

        $owner = User::query()->where('email', 'noor@example.com')->first();

        $this->assertNotNull($owner, 'an owner must be created');
        $this->assertSame('vendor_owner', $owner->role->value);

        $this->assertDatabaseHas(Invitation::class);
    }

    public function test_the_phone_is_optional(): void
    {
        Mail::fake();

        $payload = $this->payload();
        unset($payload['owner_phone']);

        $this->post(route('register.store'), $payload)->assertRedirect(route('home'));

        $this->assertDatabaseHas(User::class, ['email' => 'noor@example.com']);
    }

    public function test_a_honeypot_submission_succeeds_without_creating_anything(): void
    {
        $response = $this->post(route('register.store'), [
            ...$this->payload(),
            'website' => 'http://spam.example.com',
        ]);

        $response->assertRedirect(route('home'))->assertSessionHas('status');

        $this->assertDatabaseEmpty(User::class);
        $this->assertDatabaseEmpty(Store::class);
    }

    public function test_a_reserved_slug_is_rejected(): void
    {
        Mail::fake();

        foreach (['admin', 'blog', 'sitemap', 'about'] as $reserved) {
            $this->from('/')
                ->post(route('register.store'), $this->payload(['store_slug' => $reserved]))
                ->assertRedirect('/')
                ->assertSessionHasErrors('store_slug');
        }

        $this->assertDatabaseEmpty(Store::class);
    }

    public function test_a_duplicate_owner_email_is_rejected(): void
    {
        Mail::fake();

        User::factory()->create(['email' => 'noor@example.com']);

        $this->post(route('register.store'), $this->payload())->assertSessionHasErrors('owner_email');

        $this->assertDatabaseEmpty(Store::class);
    }

    public function test_registration_is_throttled(): void
    {
        Mail::fake();

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->post(route('register.store'), $this->payload([
                'store_slug' => 'store-'.$attempt,
                'owner_email' => "noor-$attempt@example.com",
            ]));
        }

        $this->post(route('register.store'), $this->payload([
            'store_slug' => 'store-overflow',
            'owner_email' => 'noor-overflow@example.com',
        ]))->assertStatus(429);
    }
}
