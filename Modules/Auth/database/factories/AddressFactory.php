<?php

namespace Modules\Auth\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\Address;
use Modules\Auth\Models\Customer;
use Modules\Tenancy\Models\Store;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'customer_id' => fn (array $attributes) => Customer::factory()->create(['store_id' => $attributes['store_id']])->id,
            'label' => 'المنزل',
            'recipient_name' => fake()->name(),
            'phone' => '+201012345678',
            'governorate' => 'cairo',
            'city' => 'مدينة نصر',
            'address_line' => fake()->streetAddress(),
            'notes' => null,
            'is_default' => false,
        ];
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(['store_id' => $customer->store_id, 'customer_id' => $customer->id]);
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
