<?php

namespace Modules\Catalog\Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Actions\HideProduct;
use Modules\Catalog\Actions\PublishProduct;
use Modules\Catalog\Actions\RestoreProduct;
use Modules\Catalog\Actions\UnpublishProduct;
use Modules\Catalog\Contracts\ProductSearch;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductMedia;
use Modules\Catalog\Models\Variant;
use Modules\Core\Money\Money;
use Modules\Core\Support\StoreContext;
use Modules\Tenancy\Models\Store;
use Modules\Tenancy\Testing\AssertsStoreIsolation;
use Tests\TestCase;

class CatalogDomainTest extends TestCase
{
    use AssertsStoreIsolation;
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->store = Store::factory()->create();
        app(StoreContext::class)->set($this->store->id);
    }

    private function buyableProduct(array $attributes = []): Product
    {
        $product = Product::factory()->create(['store_id' => $this->store->id, ...$attributes]);
        Variant::factory()->forProduct($product)->create();
        ProductMedia::factory()->forProduct($product)->create();

        return $product;
    }

    public function test_isolation_between_stores_for_every_catalog_model(): void
    {
        $this->assertModelIsIsolatedBetweenStores(Category::class, fn (Store $store) => Category::factory()->create(['store_id' => $store->id]));
        $this->assertModelIsIsolatedBetweenStores(Product::class, fn (Store $store) => Product::factory()->create(['store_id' => $store->id]));
        $this->assertModelIsIsolatedBetweenStores(Variant::class, fn (Store $store) => Variant::factory()->create(['store_id' => $store->id]));
        $this->assertModelIsIsolatedBetweenStores(ProductMedia::class, fn (Store $store) => ProductMedia::factory()->create(['store_id' => $store->id]));
    }

    public function test_slugs_and_skus_are_unique_per_store_only(): void
    {
        $other = Store::factory()->create();
        Category::factory()->create(['store_id' => $this->store->id, 'slug' => 'abayas']);
        Category::factory()->create(['store_id' => $other->id, 'slug' => 'abayas']);

        $this->expectException(QueryException::class);
        Category::factory()->create(['store_id' => $this->store->id, 'slug' => 'abayas']);
    }

    public function test_a_sku_may_repeat_across_stores_but_not_within_one(): void
    {
        $other = Store::factory()->create();
        Variant::factory()->create(['store_id' => $this->store->id, 'sku' => 'A-1']);
        Variant::factory()->create(['store_id' => $other->id, 'sku' => 'A-1']);

        $this->expectException(QueryException::class);
        Variant::factory()->create(['store_id' => $this->store->id, 'sku' => 'A-1']);
    }

    public function test_size_and_colour_are_unique_within_a_product(): void
    {
        $product = Product::factory()->create(['store_id' => $this->store->id]);
        Variant::factory()->forProduct($product)->create(['size' => 'M', 'color' => 'Black']);

        $this->expectException(QueryException::class);
        Variant::factory()->forProduct($product)->create(['size' => 'M', 'color' => 'Black']);
    }

    public function test_categories_are_limited_to_two_levels(): void
    {
        $top = Category::factory()->create(['store_id' => $this->store->id]);
        $child = Category::factory()->childOf($top)->create();

        $this->expectException(ValidationException::class);
        Category::factory()->childOf($child)->create();
    }

    public function test_a_category_with_children_cannot_become_a_child(): void
    {
        $top = Category::factory()->create(['store_id' => $this->store->id]);
        Category::factory()->childOf($top)->create();
        $other = Category::factory()->create(['store_id' => $this->store->id]);

        $this->expectException(ValidationException::class);
        $top->update(['parent_id' => $other->id]);
    }

    public function test_a_category_cannot_be_deleted_while_in_use(): void
    {
        $withProduct = Category::factory()->create(['store_id' => $this->store->id]);
        Product::factory()->create(['store_id' => $this->store->id, 'category_id' => $withProduct->id]);

        $withChild = Category::factory()->create(['store_id' => $this->store->id]);
        Category::factory()->childOf($withChild)->create();

        foreach ([$withProduct, $withChild] as $category) {
            try {
                $category->delete();
                $this->fail('An in-use category must not be deleted.');
            } catch (ValidationException) {
                $this->assertTrue($category->exists);
            }
        }

        $empty = Category::factory()->create(['store_id' => $this->store->id]);
        $this->assertTrue($empty->delete());
    }

    public function test_the_sale_price_must_be_lower_than_the_price(): void
    {
        $this->expectException(ValidationException::class);
        Product::factory()->create(['store_id' => $this->store->id, 'base_price' => 10000, 'sale_price' => 10000]);
    }

    public function test_prices_are_money_and_a_variant_override_wins(): void
    {
        $product = Product::factory()->create(['store_id' => $this->store->id, 'base_price' => 100000, 'sale_price' => 80000]);
        $plain = Variant::factory()->forProduct($product)->create();
        $special = Variant::factory()->forProduct($product)->create(['price_override' => 90000]);

        $this->assertInstanceOf(Money::class, $product->basePrice());
        $this->assertSame(80000, $product->currentPrice()->piasters());
        $this->assertSame(80000, $plain->price()->piasters());
        $this->assertSame(90000, $special->price()->piasters());
    }

    public function test_only_published_products_in_shown_categories_are_visible(): void
    {
        $visible = $this->buyableProduct(['status' => ProductStatus::Published]);
        $this->buyableProduct();
        $this->buyableProduct(['status' => ProductStatus::HiddenByAdmin]);
        $inactive = Category::factory()->inactive()->create(['store_id' => $this->store->id]);
        $this->buyableProduct(['status' => ProductStatus::Published, 'category_id' => $inactive->id]);
        $child = Category::factory()->childOf($inactive)->create();
        $this->buyableProduct(['status' => ProductStatus::Published, 'category_id' => $child->id]);

        $this->assertSame([$visible->id], Product::query()->published()->pluck('id')->all());
    }

    public function test_in_stock_ignores_products_whose_variants_are_all_taken(): void
    {
        $inStock = Product::factory()->create(['store_id' => $this->store->id]);
        Variant::factory()->forProduct($inStock)->stock(2, 1)->create();
        $soldOut = Product::factory()->create(['store_id' => $this->store->id]);
        Variant::factory()->forProduct($soldOut)->stock(2, 2)->create();

        $this->assertSame([$inStock->id], Product::query()->inStock()->pluck('id')->all());
    }

    public function test_a_ninth_image_is_refused(): void
    {
        $product = Product::factory()->create(['store_id' => $this->store->id]);
        ProductMedia::factory()->forProduct($product)->count(ProductMedia::MAX_PER_PRODUCT)->create();

        $this->expectException(ValidationException::class);
        ProductMedia::factory()->forProduct($product)->create();
    }

    public function test_an_uploaded_image_gets_three_webp_sizes_and_keeps_its_original(): void
    {
        $product = Product::factory()->create(['store_id' => $this->store->id]);
        $path = "stores/{$this->store->id}/products/photo.png";
        Storage::disk('public')->put($path, $this->pngBytes());

        $media = ProductMedia::factory()->forProduct($product)->create(['path' => $path]);

        $media->refresh();
        $this->assertNotNull($media->processed_at);
        Storage::disk('public')->assertExists($path);

        foreach (ProductMedia::SIZES as $size) {
            Storage::disk('public')->assertExists($media->derivedPath($size));
            $this->assertStringEndsWith("-{$size}.webp", $media->url($size));
        }
    }

    public function test_the_original_is_served_until_sizes_exist(): void
    {
        $media = ProductMedia::factory()->create(['store_id' => $this->store->id]);

        $this->assertStringEndsWith(basename($media->path), $media->url('thumb'));
    }

    public function test_publishing_needs_a_variant_and_an_image(): void
    {
        $bare = Product::factory()->create(['store_id' => $this->store->id]);

        try {
            app(PublishProduct::class)($bare);
            $this->fail('A product with no variant must not publish.');
        } catch (ValidationException) {
        }

        Variant::factory()->forProduct($bare)->create();

        try {
            app(PublishProduct::class)($bare);
            $this->fail('A product with no image must not publish.');
        } catch (ValidationException) {
        }

        ProductMedia::factory()->forProduct($bare)->create();
        app(PublishProduct::class)($bare);

        $this->assertSame(ProductStatus::Published, $bare->fresh()->status);

        app(UnpublishProduct::class)($bare);
        $this->assertSame(ProductStatus::Draft, $bare->fresh()->status);
    }

    public function test_the_platform_hides_with_a_reason_and_the_vendor_cannot_republish(): void
    {
        $product = $this->buyableProduct(['status' => ProductStatus::Published]);

        try {
            app(HideProduct::class)($product, '  ');
            $this->fail('A reason is mandatory.');
        } catch (ValidationException) {
        }

        app(HideProduct::class)($product, 'Counterfeit');
        $this->assertSame(ProductStatus::HiddenByAdmin, $product->fresh()->status);
        $this->assertSame('Counterfeit', $product->fresh()->hide_reason);
        $this->assertSame(0, Product::query()->published()->count());

        foreach ([PublishProduct::class, UnpublishProduct::class] as $vendorAction) {
            try {
                app($vendorAction)($product->fresh());
                $this->fail('The vendor must not bypass moderation.');
            } catch (ValidationException) {
            }
        }

        app(RestoreProduct::class)($product->fresh());
        $this->assertSame(ProductStatus::Draft, $product->fresh()->status);
        $this->assertNull($product->fresh()->hide_reason);
    }

    public function test_restoring_a_product_that_is_not_hidden_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        app(RestoreProduct::class)($this->buyableProduct());
    }

    public function test_search_matches_names_in_the_current_store_only_and_escapes_wildcards(): void
    {
        $match = $this->buyableProduct(['status' => ProductStatus::Published, 'name' => 'Black Abaya']);
        $this->buyableProduct(['status' => ProductStatus::Published, 'name' => 'Scarf']);
        $this->buyableProduct(['status' => ProductStatus::Draft, 'name' => 'Draft Abaya']);
        $other = Store::factory()->create();
        app(StoreContext::class)->runAs($other->id, fn () => Product::factory()->published()->create(['store_id' => $other->id, 'name' => 'Abaya elsewhere']));

        $search = app(ProductSearch::class);

        $this->assertSame([$match->id], $search->search('abaya')->pluck('id')->all());
        $this->assertSame([], $search->search('%')->pluck('id')->all());
        $this->assertSame([], $search->search('_')->pluck('id')->all());
    }

    private function pngBytes(): string
    {
        $image = imagecreatetruecolor(1600, 900);
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }
}
