<?php

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductMedia;
use Modules\Tenancy\Models\Store;

/**
 * @extends Factory<ProductMedia>
 */
class ProductMediaFactory extends Factory
{
    protected $model = ProductMedia::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'product_id' => fn (array $attributes) => Product::factory()->create(['store_id' => $attributes['store_id']])->id,
            'path' => fn (array $attributes) => "stores/{$attributes['store_id']}/products/".fake()->uuid().'.jpg',
            'sort' => 0,
        ];
    }

    public function forProduct(Product $product): static
    {
        return $this->state(['store_id' => $product->store_id, 'product_id' => $product->id]);
    }
}
