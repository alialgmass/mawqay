<?php

namespace Modules\Storefront\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductMedia;
use Modules\Catalog\Models\Variant;
use Modules\Core\Support\StoreContext;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class CatalogPagesTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->store = Store::factory()->create(['slug' => 'noor', 'name' => 'Noor Hijab']);
        app(StoreContext::class)->set($this->store->id);
    }

    private function category(array $attributes = []): Category
    {
        return Category::factory()->create(['store_id' => $this->store->id, ...$attributes]);
    }

    private function product(array $attributes = [], bool $withMedia = true): Product
    {
        $product = Product::factory()->published()->create(['store_id' => $this->store->id, ...$attributes]);
        Variant::factory()->forProduct($product)->create();

        if ($withMedia) {
            ProductMedia::factory()->forProduct($product)->create();
        }

        return $product;
    }

    public function test_the_home_page_lists_latest_published_products_and_top_level_categories(): void
    {
        $top = $this->category(['name' => 'Abayas', 'slug' => 'abayas']);
        Category::factory()->childOf($top)->create();
        $this->category(['is_active' => false]);
        $visible = $this->product(['name' => 'Black Abaya', 'category_id' => $top->id]);
        Product::factory()->create(['store_id' => $this->store->id, 'category_id' => $top->id, 'name' => 'Draft one']);

        $this->get('/noor')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('storefront/default/Home')
                ->has('latest', 1)
                ->where('latest.0.name', 'Black Abaya')
                ->where('latest.0.url', url('/noor/products/'.$visible->slug))
                ->has('categories', 1)
                ->where('categories.0.name', 'Abayas'));
    }

    public function test_a_product_card_shows_prices_as_egp_and_the_sale_price_when_discounted(): void
    {
        $this->product(['base_price' => 150000, 'sale_price' => 120000]);

        $this->get('/noor')->assertInertia(fn (Assert $page) => $page
            ->where('latest.0.price', '1,200 EGP')
            ->where('latest.0.originalPrice', '1,500 EGP'));
    }

    public function test_the_category_page_includes_sub_categories_and_hides_other_products(): void
    {
        $top = $this->category(['slug' => 'abayas']);
        $child = Category::factory()->childOf($top)->create();
        $other = $this->category();
        $inTop = $this->product(['category_id' => $top->id]);
        $inChild = $this->product(['category_id' => $child->id]);
        $this->product(['category_id' => $other->id]);

        $this->get('/noor/categories/abayas')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('storefront/default/Category')
                ->where('products.total', 2)
                ->where('breadcrumbs.0.name', 'Noor Hijab')
                ->where('breadcrumbs.1.name', $top->name));

        $this->assertNotNull($inTop);
        $this->assertNotNull($inChild);
    }

    public function test_the_category_page_paginates_and_sorts(): void
    {
        $category = $this->category(['slug' => 'all']);
        $cheap = $this->product(['category_id' => $category->id, 'base_price' => 10000]);
        $dear = $this->product(['category_id' => $category->id, 'base_price' => 90000]);
        $sale = $this->product(['category_id' => $category->id, 'base_price' => 200000, 'sale_price' => 5000]);
        foreach (range(1, 12) as $unused) {
            $this->product(['category_id' => $category->id, 'base_price' => 50000]);
        }

        $this->get('/noor/categories/all?sort=price_asc')->assertInertia(fn (Assert $page) => $page
            ->where('products.total', 15)
            ->has('products.data', 12)
            ->where('products.data.0.name', $sale->name)
            ->where('products.data.1.name', $cheap->name)
            ->where('sort', 'price_asc'));

        $this->get('/noor/categories/all?sort=price_desc')->assertInertia(fn (Assert $page) => $page
            ->where('products.data.0.name', $dear->name));

        $this->get('/noor/categories/all?sort=newest&page=2')->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 3)
            ->where('products.currentPage', 2));

        $this->get('/noor/categories/all?sort=nonsense')->assertInertia(fn (Assert $page) => $page->where('sort', 'newest'));
    }

    public function test_an_empty_or_inactive_or_unknown_category(): void
    {
        $this->category(['slug' => 'empty']);
        $this->category(['slug' => 'off', 'is_active' => false]);

        $this->get('/noor/categories/empty')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('products.total', 0));
        $this->get('/noor/categories/off')->assertNotFound();
        $this->get('/noor/categories/nothing')->assertNotFound();
    }

    public function test_the_product_page_offers_variants_and_marks_unavailable_ones(): void
    {
        $category = $this->category(['slug' => 'abayas']);
        $product = Product::factory()->published()->create([
            'store_id' => $this->store->id, 'category_id' => $category->id, 'slug' => 'black-abaya',
            'name' => 'Black Abaya', 'base_price' => 150000, 'description' => '<p>Soft <strong>crepe</strong></p><script>alert(1)</script>',
        ]);
        Variant::factory()->forProduct($product)->create(['size' => 'M', 'color' => 'Black', 'stock' => 3, 'reserved' => 3]);
        Variant::factory()->forProduct($product)->create(['size' => 'L', 'color' => 'Black', 'stock' => 4, 'price_override' => 160000]);
        ProductMedia::factory()->forProduct($product)->count(2)->create();

        $this->get('/noor/products/black-abaya')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('storefront/default/Product')
                ->where('product.name', 'Black Abaya')
                ->where('product.price', '1,500 EGP')
                ->has('product.images', 2)
                ->has('product.variants', 2)
                ->where('product.variants.0.available', false)
                ->where('product.variants.1.available', true)
                ->where('product.variants.1.price', '1,600 EGP')
                ->where('breadcrumbs.1.name', $category->name));
    }

    public function test_the_product_description_is_served_as_plain_text(): void
    {
        $this->product(['slug' => 'p', 'description' => '<p>Soft</p><p>Light <b>weave</b></p><script>alert(1)</script>']);

        $this->get('/noor/products/p')->assertInertia(fn (Assert $page) => $page
            ->where('product.description', "Soft\nLight weave\nalert(1)"));
    }

    public function test_seo_tags_use_the_product_and_its_image(): void
    {
        $product = $this->product(['slug' => 'p', 'name' => 'Black Abaya', 'description' => '<p>'.str_repeat('word ', 80).'</p>']);

        $this->get('/noor/products/p')->assertInertia(fn (Assert $page) => $page
            ->where('seo.title', 'Black Abaya | Noor Hijab')
            ->where('seo.image', $product->media()->first()->url('large'))
            ->where('seo.description', fn ($description) => mb_strlen($description) <= 160 && $description !== ''));
    }

    public function test_draft_hidden_and_inactive_category_products_are_not_found_by_link(): void
    {
        $this->product(['slug' => 'draft', 'status' => ProductStatus::Draft]);
        $this->product(['slug' => 'hidden', 'status' => ProductStatus::HiddenByAdmin]);
        $off = $this->category(['is_active' => false]);
        $this->product(['slug' => 'in-off', 'category_id' => $off->id]);

        foreach (['draft', 'hidden', 'in-off', 'missing'] as $slug) {
            $this->get("/noor/products/{$slug}")->assertNotFound();
        }

        $this->get('/noor')->assertInertia(fn (Assert $page) => $page->has('latest', 0));
    }

    public function test_search_finds_published_products_by_name(): void
    {
        $this->product(['name' => 'Black Abaya']);
        $this->product(['name' => 'White Scarf']);
        Product::factory()->create(['store_id' => $this->store->id, 'name' => 'Draft Abaya']);

        $this->get('/noor/search?q=abaya')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('storefront/default/Search')
                ->where('query', 'abaya')
                ->where('products.total', 1)
                ->where('products.data.0.name', 'Black Abaya'));

        $this->get('/noor/search?q=zzz')->assertInertia(fn (Assert $page) => $page->where('products.total', 0));
        $this->get('/noor/search')->assertInertia(fn (Assert $page) => $page->where('products.total', 0));
        $this->get('/noor/search?q=%25')->assertInertia(fn (Assert $page) => $page->where('products.total', 0));
    }

    public function test_one_stores_products_never_appear_in_another_stores_pages(): void
    {
        $other = Store::factory()->create(['slug' => 'other']);
        $foreign = app(StoreContext::class)->runAs($other->id, function () use ($other) {
            $category = Category::factory()->create(['store_id' => $other->id, 'slug' => 'abayas']);
            $product = Product::factory()->published()->create(['store_id' => $other->id, 'category_id' => $category->id, 'slug' => 'foreign', 'name' => 'Foreign abaya']);
            Variant::factory()->forProduct($product)->create();

            return $product;
        });
        $this->product(['name' => 'Mine']);

        $this->get('/noor')->assertInertia(fn (Assert $page) => $page->has('latest', 1)->where('latest.0.name', 'Mine'));
        $this->get('/noor/products/foreign')->assertNotFound();
        $this->get('/noor/categories/abayas')->assertNotFound();
        $this->get('/noor/search?q=foreign')->assertInertia(fn (Assert $page) => $page->where('products.total', 0));
        $this->get('/other/products/foreign')->assertOk();
        $this->assertNotNull($foreign);
    }

    public function test_a_draft_store_hides_its_catalog_from_the_public_and_a_suspended_one_shows_maintenance(): void
    {
        $product = $this->product(['slug' => 'p']);

        $this->store->update(['status' => 'draft']);
        $this->get('/noor/products/p')->assertNotFound();

        $this->store->update(['status' => 'suspended']);
        $this->get('/noor/products/p')->assertStatus(503);
        $this->assertNotNull($product);
    }
}
