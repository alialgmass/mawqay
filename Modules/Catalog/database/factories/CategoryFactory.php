<?php

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\Category;
use Modules\Tenancy\Models\Store;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'parent_id' => null,
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(2).'-'.fake()->unique()->numerify('###'),
            'sort' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function childOf(Category $parent): static
    {
        return $this->state(['store_id' => $parent->store_id, 'parent_id' => $parent->id]);
    }
}
