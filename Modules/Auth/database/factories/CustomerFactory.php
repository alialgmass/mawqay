<?php

namespace Modules\Auth\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\Customer;
use Modules\Tenancy\Models\Store;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => null,
            'password' => 'password',
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function verified(): static
    {
        return $this->state(['email_verified_at' => now()]);
    }
}
