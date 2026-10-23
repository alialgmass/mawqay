<?php

namespace Modules\Tenancy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Tenancy\Enums\StoreStatus;
use Modules\Tenancy\Models\Store;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    protected $model = Store::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'name' => $name,
            'status' => StoreStatus::Active,
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => '+2010'.fake()->numerify('########'),
        ];
    }

    public function suspended(): static
    {
        return $this->state(['status' => StoreStatus::Suspended]);
    }

    public function draft(): static
    {
        return $this->state(['status' => StoreStatus::Draft]);
    }
}
