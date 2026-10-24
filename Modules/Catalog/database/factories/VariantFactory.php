<?php

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\Variant;
use Modules\Tenancy\Models\Store;

/**
 * @extends Factory<Variant>
 */
class VariantFactory extends Factory
{
    protected $model = Variant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'product_id' => fn (array $attributes) => Product::factory()->create(['store_id' => $attributes['store_id']])->id,
            'sku' => 'SKU-'.fake()->unique()->numerify('######'),
            'size' => fake()->randomElement(['S', 'M', 'L', 'XL']),
            'color' => fake()->unique()->safeColorName(),
            'price_override' => null,
            'stock' => 10,
            'reserved' => 0,
        ];
    }

    public function forProduct(Product $product): static
    {
        return $this->state(['store_id' => $product->store_id, 'product_id' => $product->id]);
    }

    public function stock(int $stock, int $reserved = 0): static
    {
        return $this->state(['stock' => $stock, 'reserved' => $reserved]);
    }
}
