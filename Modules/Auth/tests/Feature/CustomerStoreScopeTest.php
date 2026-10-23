<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Modules\Auth\Models\Customer;
use Modules\Core\Concerns\BelongsToStore;
use Modules\Core\Support\StoreContext;
use Modules\Tenancy\Models\Store;
use Modules\Tenancy\Testing\AssertsStoreIsolation;
use Tests\TestCase;

class CustomerStoreScopeTest extends TestCase
{
    use AssertsStoreIsolation;
    use RefreshDatabase;

    public function test_a_customer_created_in_a_store_belongs_to_it_automatically(): void
    {
        $store = Store::factory()->create();

        $customer = app(StoreContext::class)->runAs($store->id, fn () => Customer::query()->create([
            'name' => 'Mona',
            'email' => 'mona@example.com',
            'password' => 'secret-password',
        ]));

        $this->assertSame($store->id, $customer->store_id);
    }

    public function test_creating_a_customer_with_no_store_is_refused(): void
    {
        $this->expectException(LogicException::class);

        Customer::query()->create(['name' => 'Nobody', 'email' => 'n@example.com', 'password' => 'secret-password']);
    }

    public function test_queries_in_one_store_never_return_another_stores_customers(): void
    {
        [$storeA, $storeB] = Store::factory()->count(2)->create();
        $mine = Customer::factory()->for($storeA, 'store')->create();
        $theirs = Customer::factory()->for($storeB, 'store')->create();

        app(StoreContext::class)->runAs($storeA->id, function () use ($mine, $theirs) {
            $this->assertSame([$mine->id], Customer::query()->pluck('id')->all());
            $this->assertNull(Customer::query()->find($theirs->id));
        });
    }

    public function test_queries_outside_any_store_return_nothing(): void
    {
        Customer::factory()->for(Store::factory()->create(), 'store')->create();

        $this->assertSame(0, Customer::query()->count());
    }

    public function test_the_same_email_in_two_stores_makes_two_independent_customers(): void
    {
        [$storeA, $storeB] = Store::factory()->count(2)->create();

        $inA = Customer::factory()->for($storeA, 'store')->create(['email' => 'mona@example.com', 'password' => 'password-for-a']);
        $inB = Customer::factory()->for($storeB, 'store')->create(['email' => 'mona@example.com', 'password' => 'password-for-b']);

        $this->assertNotSame($inA->id, $inB->id);
        $this->assertNotSame($inA->password, $inB->password);
    }

    public function test_the_same_email_twice_in_one_store_is_refused(): void
    {
        $store = Store::factory()->create();
        Customer::factory()->for($store, 'store')->create(['email' => 'mona@example.com']);

        $this->expectException(QueryException::class);

        Customer::factory()->for($store, 'store')->create(['email' => 'mona@example.com']);
    }

    public function test_the_same_phone_in_two_stores_makes_two_independent_customers(): void
    {
        [$storeA, $storeB] = Store::factory()->count(2)->create();

        Customer::factory()->for($storeA, 'store')->create(['email' => null, 'phone' => '+201000000001']);
        Customer::factory()->for($storeB, 'store')->create(['email' => null, 'phone' => '+201000000001']);

        $this->assertSame(2, Customer::query()->withoutGlobalScopes()->where('phone', '+201000000001')->count());
    }

    public function test_customers_are_isolated_between_stores(): void
    {
        $this->assertModelIsIsolatedBetweenStores(
            Customer::class,
            fn (Store $store) => Customer::factory()->for($store, 'store')->create(),
        );
    }

    public function test_a_later_model_joins_the_isolation_check_in_a_few_lines(): void
    {
        Schema::create('probe_notes', function ($table) {
            $table->id();
            $table->foreignId('store_id');
            $table->string('body');
            $table->timestamps();
        });

        $this->assertModelIsIsolatedBetweenStores(
            ProbeNote::class,
            fn (Store $store) => ProbeNote::query()->create(['store_id' => $store->id, 'body' => 'note']),
        );
    }
}

class ProbeNote extends Model
{
    use BelongsToStore;

    protected $table = 'probe_notes';

    protected $guarded = [];
}
