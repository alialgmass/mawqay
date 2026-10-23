<?php

namespace Modules\Core\Tests\Feature;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use LogicException;
use Modules\Core\Jobs\CarriesStore;
use Modules\Core\Support\StoreCache;
use Modules\Core\Support\StoreContext;
use Modules\Core\Support\StoreStorage;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class StoreInfrastructureIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Store $storeA;

    private Store $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = Store::factory()->create();
        $this->storeB = Store::factory()->create();
        ProbeStoreJob::$sawStoreId = false;
    }

    private function inStore(Store $store, callable $callback): mixed
    {
        return app(StoreContext::class)->runAs($store->id, $callback);
    }

    public function test_a_cached_value_is_not_readable_from_another_store(): void
    {
        $this->inStore($this->storeA, fn () => StoreCache::put('menu', 'only-for-a'));

        $this->assertSame('only-for-a', $this->inStore($this->storeA, fn () => StoreCache::get('menu')));
        $this->assertNull($this->inStore($this->storeB, fn () => StoreCache::get('menu')));
    }

    public function test_cache_keys_carry_the_store_prefix(): void
    {
        $this->inStore($this->storeA, fn () => StoreCache::put('menu', 'value'));

        $this->assertSame('value', Cache::get("store:{$this->storeA->id}:menu"));
    }

    public function test_remember_and_forget_stay_inside_the_store(): void
    {
        $this->inStore($this->storeA, fn () => StoreCache::remember('count', 60, fn () => 'a'));
        $this->inStore($this->storeB, fn () => StoreCache::remember('count', 60, fn () => 'b'));

        $this->assertSame('a', $this->inStore($this->storeA, fn () => StoreCache::get('count')));

        $this->inStore($this->storeB, fn () => StoreCache::forget('count'));

        $this->assertSame('a', $this->inStore($this->storeA, fn () => StoreCache::get('count')));
        $this->assertNull($this->inStore($this->storeB, fn () => StoreCache::get('count')));
    }

    public function test_the_cache_refuses_to_work_outside_a_store(): void
    {
        $this->expectException(LogicException::class);

        StoreCache::get('menu');
    }

    public function test_a_queued_job_runs_in_the_store_it_was_dispatched_for(): void
    {
        $job = $this->inStore($this->storeA, fn () => new ProbeStoreJob);

        dispatch_sync($job);

        $this->assertSame($this->storeA->id, ProbeStoreJob::$sawStoreId);
        $this->assertNull(app(StoreContext::class)->id(), 'the worker context is left as it was');
    }

    public function test_a_job_cannot_be_created_outside_a_store(): void
    {
        $this->expectException(LogicException::class);

        new ProbeStoreJob;
    }

    public function test_files_are_saved_under_the_stores_own_folder(): void
    {
        Storage::fake('local');

        $this->inStore($this->storeA, fn () => StoreStorage::put('docs/a.txt', 'secret'));

        Storage::disk('local')->assertExists("stores/{$this->storeA->id}/docs/a.txt");
        $this->assertTrue($this->inStore($this->storeA, fn () => StoreStorage::exists('docs/a.txt')));
    }

    public function test_another_store_cannot_reach_a_file(): void
    {
        Storage::fake('local');

        $this->inStore($this->storeA, fn () => StoreStorage::put('docs/a.txt', 'secret'));

        $this->assertFalse($this->inStore($this->storeB, fn () => StoreStorage::exists('docs/a.txt')));
    }

    public function test_a_path_cannot_climb_out_of_the_stores_folder(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->inStore($this->storeB, fn () => StoreStorage::path("../{$this->storeA->id}/docs/a.txt"));
    }
}

class ProbeStoreJob implements ShouldQueue
{
    use CarriesStore, Queueable;

    public static int|false|null $sawStoreId = false;

    public function __construct()
    {
        $this->captureStore();
    }

    public function handle(): void
    {
        self::$sawStoreId = app(StoreContext::class)->id();
    }
}
