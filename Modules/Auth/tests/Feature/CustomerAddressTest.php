<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\Address;
use Modules\Auth\Models\Customer;
use Modules\Tenancy\Models\Store;
use Modules\Tenancy\Testing\AssertsStoreIsolation;
use Tests\TestCase;

class CustomerAddressTest extends TestCase
{
    use AssertsStoreIsolation;
    use RefreshDatabase;

    private Store $storeA;

    private Store $storeB;

    private Customer $mona;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = Store::factory()->create(['slug' => 'store-a']);
        $this->storeB = Store::factory()->create(['slug' => 'store-b']);
        $this->mona = Customer::factory()->create(['store_id' => $this->storeA->id, 'email' => 'mona@example.com', 'password' => 'a-strong-password']);

        $this->postJson('/store-a/customer/login', ['identifier' => 'mona@example.com', 'password' => 'a-strong-password'])->assertOk();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'label' => 'Home',
            'recipient_name' => 'Mona Ali',
            'phone' => '01012345678',
            'governorate' => 'giza',
            'city' => 'Dokki',
            'address_line' => '12 Tahrir St',
            ...$overrides,
        ];
    }

    private function add(array $overrides = []): Address
    {
        $id = $this->postJson('/store-a/customer/addresses', $this->payload($overrides))->assertCreated()->json('id');

        return Address::query()->findOrFail($id);
    }

    public function test_the_first_address_becomes_the_default_automatically(): void
    {
        $first = $this->add();
        $second = $this->add(['label' => 'Work']);

        $this->assertTrue($first->fresh()->is_default);
        $this->assertFalse($second->fresh()->is_default);
        $this->assertSame('+201012345678', $first->phone);
        $this->assertSame($this->storeA->id, $first->store_id);
    }

    public function test_marking_a_default_leaves_exactly_one(): void
    {
        $first = $this->add();
        $second = $this->add(['label' => 'Work', 'is_default' => true]);

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);

        $this->postJson("/store-a/customer/addresses/{$first->id}/default")->assertOk();

        $this->assertSame(1, $this->mona->addresses()->where('is_default', true)->count());
        $this->assertTrue($first->fresh()->is_default);
    }

    public function test_an_address_can_be_edited_and_made_default_while_editing(): void
    {
        $first = $this->add();
        $second = $this->add(['label' => 'Work']);

        $this->putJson("/store-a/customer/addresses/{$second->id}", $this->payload(['label' => 'Office', 'city' => 'Maadi', 'is_default' => true]))->assertOk();

        $this->assertSame('Office', $second->fresh()->label);
        $this->assertSame('Maadi', $second->fresh()->city);
        $this->assertTrue($second->fresh()->is_default);
        $this->assertFalse($first->fresh()->is_default);
    }

    public function test_deleting_the_default_promotes_the_most_recent_remaining_address(): void
    {
        $first = $this->add();
        $second = $this->add(['label' => 'Work']);
        $third = $this->add(['label' => 'Parents']);

        $this->deleteJson("/store-a/customer/addresses/{$first->id}")->assertOk();

        $this->assertNull(Address::query()->find($first->id));
        $this->assertTrue($third->fresh()->is_default);
        $this->assertFalse($second->fresh()->is_default);
    }

    public function test_deleting_a_non_default_keeps_the_default(): void
    {
        $first = $this->add();
        $second = $this->add(['label' => 'Work']);

        $this->deleteJson("/store-a/customer/addresses/{$second->id}")->assertOk();

        $this->assertTrue($first->fresh()->is_default);
    }

    public function test_deleting_the_only_address_leaves_none(): void
    {
        $only = $this->add();

        $this->deleteJson("/store-a/customer/addresses/{$only->id}")->assertOk();

        $this->assertSame(0, $this->mona->addresses()->count());
    }

    public function test_validation(): void
    {
        $this->postJson('/store-a/customer/addresses', $this->payload(['governorate' => 'atlantis']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.governorate.0', __('auth::addresses.governorate_invalid', [], 'ar'));

        foreach (['label', 'recipient_name', 'phone', 'governorate', 'city', 'address_line'] as $required) {
            $this->postJson('/store-a/customer/addresses', $this->payload([$required => '']))->assertUnprocessable()->assertJsonValidationErrors([$required]);
        }

        $this->postJson('/store-a/customer/addresses', $this->payload(['phone' => '123']))->assertUnprocessable()->assertJsonValidationErrors(['phone']);
        $this->assertSame(0, Address::query()->count());
    }

    public function test_the_governorates_list_covers_egypt(): void
    {
        $this->assertCount(27, config('customers.governorates'));
        $this->assertArrayHasKey('cairo', config('customers.governorates'));
    }

    public function test_a_customer_keeps_at_most_ten_addresses(): void
    {
        foreach (range(1, 10) as $number) {
            $this->add(['label' => "Place {$number}"]);
        }

        $this->postJson('/store-a/customer/addresses', $this->payload(['label' => 'One too many']))->assertUnprocessable();
        $this->assertSame(10, $this->mona->addresses()->count());
    }

    public function test_a_customer_never_reaches_another_customers_address(): void
    {
        $neighbour = Customer::factory()->create(['store_id' => $this->storeA->id, 'email' => 'neighbour@example.com']);
        $theirs = Address::factory()->forCustomer($neighbour)->default()->create();

        $this->getJson('/store-a/customer/addresses');
        $this->putJson("/store-a/customer/addresses/{$theirs->id}", $this->payload(['label' => 'Hacked']))->assertNotFound();
        $this->deleteJson("/store-a/customer/addresses/{$theirs->id}")->assertNotFound();
        $this->postJson("/store-a/customer/addresses/{$theirs->id}/default")->assertNotFound();

        $this->assertSame('المنزل', $theirs->fresh()->label);
        $this->assertNotNull(Address::query()->find($theirs->id));
    }

    public function test_an_address_of_another_store_is_not_found_either(): void
    {
        $other = Customer::factory()->create(['store_id' => $this->storeB->id, 'email' => 'mona@example.com']);
        $foreign = Address::factory()->forCustomer($other)->create();

        $this->deleteJson("/store-a/customer/addresses/{$foreign->id}")->assertNotFound();
        $this->assertNotNull(Address::query()->withoutGlobalScopes()->find($foreign->id));
    }

    public function test_the_policy_allows_only_the_owner(): void
    {
        $address = $this->add();
        $neighbour = Customer::factory()->create(['store_id' => $this->storeA->id, 'email' => 'n@example.com']);

        $this->assertTrue(Gate::forUser($this->mona)->allows('update', $address));
        $this->assertTrue(Gate::forUser($this->mona)->allows('delete', $address));
        $this->assertFalse(Gate::forUser($neighbour)->allows('update', $address));
        $this->assertFalse(Gate::forUser($neighbour)->allows('view', $address));
        $this->assertFalse(Gate::forUser($neighbour)->allows('delete', $address));
    }

    public function test_a_signed_out_visitor_cannot_use_the_address_book(): void
    {
        $this->postJson('/store-a/customer/logout');

        $this->postJson('/store-a/customer/addresses', $this->payload())->assertUnauthorized();
    }

    public function test_addresses_are_isolated_between_stores(): void
    {
        $this->assertModelIsIsolatedBetweenStores(
            Address::class,
            fn (Store $store) => Address::factory()->forCustomer(Customer::factory()->create(['store_id' => $store->id]))->create(),
        );
    }

    public function test_deleting_a_customer_removes_their_addresses(): void
    {
        $this->add();

        $this->mona->delete();

        $this->assertSame(0, Address::query()->withoutGlobalScopes()->count());
    }
}
