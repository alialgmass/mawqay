<?php

namespace Modules\Backoffice\Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoStoresSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Core\Support\StoreContext;
use Modules\Feedback\Models\Survey;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Models\Order;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class DemoDataTest extends TestCase
{
    use RefreshDatabase;

    private function inStore(Store $store, callable $callback): mixed
    {
        return app(StoreContext::class)->runAs($store->id, $callback);
    }

    public function test_the_demo_seed_creates_two_stores_with_an_order_in_every_status(): void
    {
        $this->seed(DemoStoresSeeder::class);

        $stores = Store::query()->whereIn('slug', array_keys(DemoStoresSeeder::STORES))->get();
        $this->assertCount(2, $stores);

        foreach ($stores as $store) {
            $statuses = $this->inStore($store, fn () => Order::query()->get()->pluck('status')->map(fn (OrderStatus $status): string => $status->value)->sort()->values()->all());

            $this->assertSame(collect(OrderStatus::cases())->map(fn (OrderStatus $status): string => $status->value)->sort()->values()->all(), $statuses, $store->slug);
            $this->assertGreaterThanOrEqual(1, $this->inStore($store, fn (): int => Survey::query()->count()));
            $this->assertTrue(User::query()->where('store_id', $store->id)->exists());
        }
    }

    public function test_seeding_twice_changes_nothing(): void
    {
        $this->seed(DemoStoresSeeder::class);
        $orders = DB::table('orders')->count();

        $this->seed(DemoStoresSeeder::class);

        $this->assertSame($orders, DB::table('orders')->count());
        $this->assertSame(2, Store::query()->count());
    }

    public function test_the_wipe_removes_only_demo_data(): void
    {
        $this->seed(DemoStoresSeeder::class);
        $real = Store::factory()->create(['slug' => 'real-store']);
        $realOwner = User::factory()->vendorOwner($real)->create();
        $admin = User::factory()->admin()->create();

        $this->artisan('demo:wipe', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, Store::query()->whereIn('slug', array_keys(DemoStoresSeeder::STORES))->count());
        $this->assertSame(0, DB::table('orders')->count());
        $this->assertSame(0, DB::table('products')->count());
        $this->assertTrue(Store::query()->whereKey($real->id)->exists());
        $this->assertTrue(User::query()->whereKey($realOwner->id)->exists());
        $this->assertTrue(User::query()->whereKey($admin->id)->exists());
        $this->assertFalse(User::query()->where('email', 'like', 'owner-demo-%')->exists());
    }

    public function test_the_wipe_skips_a_demo_store_that_has_ledger_entries(): void
    {
        $this->seed(DemoStoresSeeder::class);
        $store = Store::query()->where('slug', 'demo-noor')->firstOrFail();
        DB::table('ledger_entries')->insert(['store_id' => $store->id, 'posting_group' => 'g', 'account' => 'platform_cash', 'direction' => 'debit', 'amount' => 100, 'idempotency_key' => 'k1']);

        $this->artisan('demo:wipe', ['--force' => true])->assertSuccessful();

        $this->assertTrue(Store::query()->whereKey($store->id)->exists());
        $this->assertFalse(Store::query()->where('slug', 'demo-hala')->exists());
    }

    public function test_demo_data_is_never_seeded_or_wiped_in_production(): void
    {
        $this->app['env'] = 'production';

        (new DemoStoresSeeder)->run();
        $this->assertSame(0, Store::query()->count());

        $this->app['env'] = 'testing';
        $this->seed(DemoStoresSeeder::class);
        $this->app['env'] = 'production';

        $this->artisan('demo:wipe', ['--force' => true])->assertFailed();
        $this->assertSame(2, Store::query()->count());
    }
}
