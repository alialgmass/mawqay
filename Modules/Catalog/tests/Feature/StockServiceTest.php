<?php

namespace Modules\Catalog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Modules\Catalog\Exceptions\InsufficientStock;
use Modules\Catalog\Models\Variant;
use Modules\Catalog\Services\StockService;
use Modules\Core\Support\StoreContext;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

/**
 * The race test below replays a lost race with stale models in one process.
 * The test database is in-memory SQLite, which cannot run parallel requests or
 * honour row locks, so the lock itself is only proven on MySQL/PostgreSQL.
 */
class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stock;

    private Variant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $store = Store::factory()->create();
        app(StoreContext::class)->set($store->id);

        $this->stock = app(StockService::class);
        $this->variant = Variant::factory()->stock(5)->create(['store_id' => $store->id]);
    }

    public function test_reserve_holds_quantity_without_touching_stock(): void
    {
        $this->stock->reserve($this->variant, 2);

        $fresh = $this->variant->fresh();
        $this->assertSame(5, $fresh->stock);
        $this->assertSame(2, $fresh->reserved);
        $this->assertSame(3, $fresh->available());
    }

    public function test_reserving_more_than_is_available_fails_and_changes_nothing(): void
    {
        $this->stock->reserve($this->variant, 4);

        try {
            $this->stock->reserve($this->variant, 2);
            $this->fail('Overselling must be refused.');
        } catch (InsufficientStock) {
            $this->assertSame(4, $this->variant->fresh()->reserved);
        }
    }

    public function test_release_returns_held_quantity(): void
    {
        $this->stock->reserve($this->variant, 3);
        $this->stock->release($this->variant, 2);

        $this->assertSame(1, $this->variant->fresh()->reserved);
        $this->assertSame(5, $this->variant->fresh()->stock);
    }

    public function test_release_cannot_exceed_what_is_reserved(): void
    {
        $this->stock->reserve($this->variant, 1);

        $this->expectException(InsufficientStock::class);
        $this->stock->release($this->variant, 2);
    }

    public function test_commit_turns_reserved_quantity_into_a_sale(): void
    {
        $this->stock->reserve($this->variant, 3);
        $this->stock->commit($this->variant, 2);

        $fresh = $this->variant->fresh();
        $this->assertSame(3, $fresh->stock);
        $this->assertSame(1, $fresh->reserved);
    }

    public function test_commit_cannot_exceed_what_is_reserved(): void
    {
        $this->stock->reserve($this->variant, 1);

        $this->expectException(InsufficientStock::class);
        $this->stock->commit($this->variant, 2);
    }

    public function test_the_quantity_must_be_positive(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->stock->reserve($this->variant, 0);
    }

    public function test_two_buyers_holding_stale_copies_cannot_both_take_the_last_unit(): void
    {
        $last = Variant::factory()->forProduct($this->variant->product)->stock(1)->create();

        // Both buyers loaded the Variant while one unit was still available.
        $buyerA = Variant::query()->find($last->id);
        $buyerB = Variant::query()->find($last->id);

        $successes = 0;

        foreach ([$buyerA, $buyerB] as $stale) {
            try {
                $this->stock->reserve($stale, 1);
                $successes++;
            } catch (InsufficientStock) {
                // the second buyer loses the race
            }
        }

        $this->assertSame(1, $successes);
        $this->assertSame(1, $last->fresh()->reserved);
    }

    public function test_the_given_model_reflects_the_new_counts(): void
    {
        $this->stock->reserve($this->variant, 2);

        $this->assertSame(2, $this->variant->reserved);
    }
}
