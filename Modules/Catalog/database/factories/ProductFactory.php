<?php

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Tenancy\Models\Store;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'category_id' => fn (array $attributes) => Category::factory()->create(['store_id' => $attributes['store_id']])->id,
            'name' => fake()->unique()->words(3, true),
            'slug' => fake()->unique()->slug(3).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->sentence(),
            'base_price' => fake()->numberBetween(100, 2000) * 100,
            'sale_price' => null,
            'status' => ProductStatus::Draft,
            'sort' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => ProductStatus::Published]);
    }

    public function hiddenByAdmin(string $reason = 'Violates the rules'): static
    {
        return $this->state(['status' => ProductStatus::HiddenByAdmin, 'hide_reason' => $reason]);
    }

    public function onSale(int $salePrice): static
    {
        return $this->state(['sale_price' => $salePrice]);
    }
}
