<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Auth\Models\Customer;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    private Store $storeA;

    private Store $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = Store::factory()->create(['slug' => 'store-a']);
        $this->storeB = Store::factory()->create(['slug' => 'store-b']);
    }

    private function register(string $slug, array $overrides = [])
    {
        return $this->postJson("/{$slug}/customer/register", array_merge([
            'name' => 'Mona',
            'email' => 'mona@example.com',
            'password' => 'a-strong-password',
        ], $overrides));
    }

    private function seenAs(string $slug): ?string
    {
        $name = null;

        $this->get("/{$slug}")->assertInertia(function (Assert $page) use (&$name) {
            $props = $page->toArray()['props'];
            $name = $props['customer']['name'] ?? null;
        });

        return $name;
    }

    public function test_a_customer_registers_in_a_store_and_is_signed_in_there(): void
    {
        $this->register('store-a')->assertCreated();

        $this->assertSame($this->storeA->id, Customer::query()->withoutGlobalScopes()->firstOrFail()->store_id);
        $this->assertSame('Mona', $this->seenAs('store-a'));
    }

    public function test_registration_needs_an_email_or_a_phone(): void
    {
        $this->register('store-a', ['email' => null, 'phone' => null])->assertUnprocessable();
        $this->register('store-a', ['email' => null, 'phone' => '+201000000009'])->assertCreated();
    }

    public function test_the_same_email_cannot_register_twice_in_one_store(): void
    {
        $this->register('store-a')->assertCreated();
        auth('customer')->logout();

        $this->register('store-a')->assertUnprocessable();
    }

    public function test_the_same_email_can_register_in_two_stores(): void
    {
        $this->register('store-a')->assertCreated();
        $this->register('store-b')->assertCreated();

        $this->assertSame(2, Customer::query()->withoutGlobalScopes()->where('email', 'mona@example.com')->count());
    }

    public function test_a_customer_logs_in_with_email_or_phone(): void
    {
        Customer::factory()->for($this->storeA, 'store')->create(['email' => 'mona@example.com', 'phone' => '+201000000009', 'password' => 'a-strong-password']);

        $this->postJson('/store-a/customer/login', ['identifier' => 'mona@example.com', 'password' => 'a-strong-password'])->assertOk();
        $this->postJson('/store-a/customer/logout')->assertNoContent();
        $this->postJson('/store-a/customer/login', ['identifier' => '+201000000009', 'password' => 'a-strong-password'])->assertOk();
    }

    public function test_a_wrong_password_does_not_sign_in(): void
    {
        Customer::factory()->for($this->storeA, 'store')->create(['email' => 'mona@example.com', 'password' => 'a-strong-password']);

        $this->postJson('/store-a/customer/login', ['identifier' => 'mona@example.com', 'password' => 'wrong'])->assertUnprocessable();

        $this->assertNull($this->seenAs('store-a'));
    }

    public function test_a_customer_cannot_log_in_to_a_store_they_did_not_register_in(): void
    {
        Customer::factory()->for($this->storeA, 'store')->create(['email' => 'mona@example.com', 'password' => 'a-strong-password']);

        $this->postJson('/store-b/customer/login', ['identifier' => 'mona@example.com', 'password' => 'a-strong-password'])->assertUnprocessable();
    }

    public function test_a_session_from_one_store_is_signed_out_in_another(): void
    {
        $this->register('store-a')->assertCreated();

        $this->assertSame('Mona', $this->seenAs('store-a'));
        $this->assertNull($this->seenAs('store-b'));
        $this->assertSame('Mona', $this->seenAs('store-a'), 'still signed in at the original store');
    }

    public function test_customer_sign_in_does_not_grant_access_to_the_panels(): void
    {
        $this->register('store-a')->assertCreated();

        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/vendor')->assertRedirect('/vendor/login');
    }

    public function test_repeated_login_attempts_are_throttled(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/store-a/customer/login', ['identifier' => 'mona@example.com', 'password' => 'wrong'])->assertUnprocessable();
        }

        $this->postJson('/store-a/customer/login', ['identifier' => 'mona@example.com', 'password' => 'wrong'])->assertTooManyRequests();
    }

    public function test_repeated_registrations_are_throttled(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->register('store-a', ['email' => null, 'phone' => null])->assertUnprocessable();
        }

        $this->register('store-a', ['email' => null, 'phone' => null])->assertTooManyRequests();
    }
}
