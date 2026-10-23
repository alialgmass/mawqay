<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class DemoSeedTest extends TestCase
{
    use RefreshDatabase;

    private function seedAs(string $environment): void
    {
        $this->app->detectEnvironment(fn () => $environment);

        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();
    }

    public function test_seeding_locally_creates_the_demo_admin_store_and_owner(): void
    {
        $this->seedAs('local');

        $admin = User::query()->where('email', 'admin@mawqaay.test')->firstOrFail();
        $store = Store::query()->where('slug', 'demo-store')->firstOrFail();
        $owner = User::query()->where('email', 'owner@mawqaay.test')->firstOrFail();

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($owner)->get('/vendor/demo-store')->assertOk();
        $this->get('/demo-store')->assertOk();
        $this->assertSame($store->id, $owner->store_id);
    }

    public function test_the_demo_accounts_can_sign_in_with_the_documented_password(): void
    {
        $this->seedAs('local');

        $this->assertTrue(auth()->validate(['email' => 'admin@mawqaay.test', 'password' => 'password']));
        $this->assertTrue(auth()->validate(['email' => 'owner@mawqaay.test', 'password' => 'password']));
    }

    public function test_seeding_in_production_creates_nothing(): void
    {
        $this->seedAs('production');

        $this->assertSame(0, User::query()->count());
        $this->assertSame(0, Store::query()->count());
    }

    public function test_seeding_twice_does_not_duplicate(): void
    {
        $this->seedAs('local');
        $this->seedAs('local');

        $this->assertSame(2, User::query()->count());
        $this->assertSame(1, Store::query()->count());
    }
}
