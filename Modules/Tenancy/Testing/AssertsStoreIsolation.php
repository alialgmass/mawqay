<?php

namespace Modules\Tenancy\Testing;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Support\AdminScope;
use Modules\Core\Support\StoreContext;
use Modules\Tenancy\Models\Store;

/**
 * Reusable proof that one Store cannot read, list, count or modify another
 * Store's rows. Every phase adds its Store-owned models to a test using this.
 */
trait AssertsStoreIsolation
{
    /**
     * @param  class-string<Model>  $modelClass
     * @param  Closure(Store): Model  $makeForStore  creates one row owned by the given Store
     */
    protected function assertModelIsIsolatedBetweenStores(string $modelClass, Closure $makeForStore): void
    {
        $storeA = Store::factory()->create();
        $storeB = Store::factory()->create();

        $rowA = $makeForStore($storeA);
        $rowB = $makeForStore($storeB);

        $context = app(StoreContext::class);

        $context->runAs($storeA->id, function () use ($modelClass, $rowA, $rowB): void {
            $this->assertSame(1, $modelClass::query()->count(), 'count must only include the current Store');
            $this->assertSame([$rowA->getKey()], $modelClass::query()->get()->modelKeys(), 'list must only include the current Store');
            $this->assertNotNull($modelClass::query()->find($rowA->getKey()), 'own row must be readable');
            $this->assertNull($modelClass::query()->find($rowB->getKey()), 'other Store row must not be readable');

            $updated = $modelClass::query()->whereKey($rowB->getKey())->update(['updated_at' => now()->addDay()]);
            $this->assertSame(0, $updated, 'other Store row must not be modifiable');

            $deleted = $modelClass::query()->whereKey($rowB->getKey())->delete();
            $this->assertSame(0, $deleted, 'other Store row must not be deletable');
        });

        $this->assertTrue(
            AdminScope::on($modelClass)->whereKey($rowB->getKey())->exists(),
            'the other Store row must still exist after the attempts',
        );
    }
}
