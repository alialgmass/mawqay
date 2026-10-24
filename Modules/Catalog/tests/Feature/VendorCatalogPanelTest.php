<?php

namespace Modules\Catalog\Tests\Feature;

use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Filament\Vendor\Resources\Categories\Pages\ManageCategories;
use Modules\Catalog\Filament\Vendor\Resources\Products\Pages\CreateProduct;
use Modules\Catalog\Filament\Vendor\Resources\Products\Pages\EditProduct;
use Modules\Catalog\Filament\Vendor\Resources\Products\Pages\ListProducts;
use Modules\Catalog\Filament\Vendor\Widgets\CatalogStatsWidget;
use Modules\Catalog\Filament\Vendor\Widgets\LowStockWidget;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductMedia;
use Modules\Catalog\Models\Variant;
use Modules\Core\Support\StoreContext;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class VendorCatalogPanelTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private Store $otherStore;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->store = Store::factory()->create(['slug' => 'store-a']);
        $this->otherStore = Store::factory()->create(['slug' => 'store-b']);
        $this->owner = User::factory()->vendorOwner($this->store)->create();

        $this->actingAs($this->owner);
        Filament::setCurrentPanel('vendor');
        Filament::setTenant($this->store);
        app(StoreContext::class)->set($this->store->id);
    }

    private function productPayload(Category $category, array $overrides = []): array
    {
        return [
            'name' => 'Black Abaya',
            'slug' => 'black-abaya',
            'category_id' => $category->id,
            'base_price' => 1500,
            'variants' => [
                ['sku' => 'ABA-M-BLK', 'size' => 'M', 'color' => 'Black', 'stock' => 5],
            ],
            ...$overrides,
        ];
    }

    public function test_the_owner_builds_a_two_level_category_tree(): void
    {
        Livewire::test(ManageCategories::class)
            ->callAction(TestAction::make('create')->table(), ['name' => 'Abayas', 'slug' => 'abayas', 'is_active' => true])
            ->assertHasNoActionErrors();

        $top = Category::query()->where('slug', 'abayas')->firstOrFail();
        $this->assertSame($this->store->id, $top->store_id);

        Livewire::test(ManageCategories::class)
            ->callAction(TestAction::make('create')->table(), ['name' => 'Evening', 'slug' => 'evening', 'parent_id' => $top->id, 'is_active' => true])
            ->assertHasNoActionErrors();

        $this->assertSame($top->id, Category::query()->where('slug', 'evening')->value('parent_id'));
    }

    public function test_a_category_slug_is_unique_only_within_the_store(): void
    {
        Category::factory()->create(['store_id' => $this->otherStore->id, 'slug' => 'abayas']);
        Category::factory()->create(['store_id' => $this->store->id, 'slug' => 'scarves']);

        Livewire::test(ManageCategories::class)
            ->callAction(TestAction::make('create')->table(), ['name' => 'Abayas', 'slug' => 'abayas', 'is_active' => true])
            ->assertHasNoActionErrors();

        Livewire::test(ManageCategories::class)
            ->callAction(TestAction::make('create')->table(), ['name' => 'Scarves again', 'slug' => 'scarves', 'is_active' => true])
            ->assertHasActionErrors(['slug']);
    }

    public function test_a_category_in_use_is_not_deleted(): void
    {
        $category = Category::factory()->create(['store_id' => $this->store->id]);
        Product::factory()->create(['store_id' => $this->store->id, 'category_id' => $category->id]);

        Livewire::test(ManageCategories::class)
            ->callAction(TestAction::make('delete')->table($category));

        $this->assertNotNull(Category::query()->find($category->id));
    }

    public function test_the_owner_only_sees_their_own_categories_and_products(): void
    {
        $mine = Category::factory()->create(['store_id' => $this->store->id]);
        $theirs = Category::factory()->create(['store_id' => $this->otherStore->id]);
        $myProduct = Product::factory()->create(['store_id' => $this->store->id]);
        $theirProduct = Product::factory()->create(['store_id' => $this->otherStore->id]);

        Livewire::test(ManageCategories::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);

        Livewire::test(ListProducts::class)
            ->assertCanSeeTableRecords([$myProduct])
            ->assertCanNotSeeTableRecords([$theirProduct]);
    }

    public function test_the_owner_creates_a_product_with_a_variant_and_images(): void
    {
        $category = Category::factory()->create(['store_id' => $this->store->id]);

        Livewire::test(CreateProduct::class)
            ->fillForm($this->productPayload($category, [
                'sale_price' => 1200,
                'media' => [['path' => [UploadedFile::fake()->image('front.jpg', 800, 800)]]],
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::query()->where('slug', 'black-abaya')->firstOrFail();
        $this->assertSame(ProductStatus::Draft, $product->status);
        $this->assertSame(150000, $product->base_price);
        $this->assertSame(120000, $product->sale_price);
        $this->assertSame($this->store->id, $product->store_id);
        $this->assertSame(5, $product->variants()->first()->stock);

        $media = $product->media()->get();
        $this->assertCount(1, $media);
        $this->assertStringStartsWith("stores/{$this->store->id}/products/", $media->first()->path);
        $this->assertNotNull($media->first()->fresh()->processed_at);
    }

    public function test_product_validation(): void
    {
        $category = Category::factory()->create(['store_id' => $this->store->id]);
        Product::factory()->create(['store_id' => $this->store->id, 'slug' => 'taken']);
        Variant::factory()->create(['store_id' => $this->store->id, 'sku' => 'USED']);

        $baseline = Product::query()->count();
        $page = fn () => Livewire::test(CreateProduct::class);

        $page()->fillForm($this->productPayload($category, ['sale_price' => 1500]))
            ->call('create')->assertHasFormErrors(['sale_price']);

        $page()->fillForm($this->productPayload($category, ['slug' => 'taken']))
            ->call('create')->assertHasFormErrors(['slug']);

        $page()->fillForm($this->productPayload($category, [
            'variants' => [['sku' => 'USED', 'size' => 'M', 'color' => 'Black', 'stock' => 1]],
        ]))->call('create')->assertHasFormErrors();

        $page()->fillForm($this->productPayload($category, [
            'variants' => [['sku' => 'X', 'size' => 'M', 'color' => 'Black', 'stock' => -1]],
        ]))->call('create')->assertHasFormErrors();

        $page()->fillForm($this->productPayload($category, [
            'variants' => [
                ['sku' => 'A', 'size' => 'M', 'color' => 'Black', 'stock' => 1],
                ['sku' => 'B', 'size' => 'm', 'color' => 'black', 'stock' => 1],
            ],
        ]))->call('create')->assertHasFormErrors(['variants']);

        $page()->fillForm($this->productPayload($category, [
            'media' => [['path' => [UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')]]],
        ]))->call('create')->assertHasFormErrors();

        $this->assertSame($baseline, Product::query()->count());
    }

    public function test_a_ninth_image_cannot_be_added_in_the_form(): void
    {
        $category = Category::factory()->create(['store_id' => $this->store->id]);
        $images = collect(range(1, 9))->map(fn () => ['path' => [UploadedFile::fake()->image('p.jpg')]])->all();

        Livewire::test(CreateProduct::class)
            ->fillForm($this->productPayload($category, ['media' => $images]))
            ->call('create')
            ->assertHasFormErrors();

        $this->assertSame(0, ProductMedia::query()->count());
    }

    public function test_publish_and_unpublish_from_the_edit_page(): void
    {
        $product = Product::factory()->create(['store_id' => $this->store->id]);
        Variant::factory()->forProduct($product)->create();
        ProductMedia::factory()->forProduct($product)->create();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->callAction('publish');
        $this->assertSame(ProductStatus::Published, $product->fresh()->status);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->callAction('unpublish');
        $this->assertSame(ProductStatus::Draft, $product->fresh()->status);
    }

    public function test_publishing_an_incomplete_product_is_refused(): void
    {
        $product = Product::factory()->create(['store_id' => $this->store->id]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->callAction('publish');

        $this->assertSame(ProductStatus::Draft, $product->fresh()->status);
    }

    public function test_bulk_publish_skips_what_cannot_be_published(): void
    {
        $ready = Product::factory()->create(['store_id' => $this->store->id]);
        Variant::factory()->forProduct($ready)->create();
        ProductMedia::factory()->forProduct($ready)->create();
        $bare = Product::factory()->create(['store_id' => $this->store->id]);
        $hidden = Product::factory()->hiddenByAdmin()->create(['store_id' => $this->store->id]);

        Livewire::test(ListProducts::class)
            ->selectTableRecords([$ready->id, $bare->id, $hidden->id])
            ->callAction(TestAction::make('publish')->table()->bulk());

        $this->assertSame(ProductStatus::Published, $ready->fresh()->status);
        $this->assertSame(ProductStatus::Draft, $bare->fresh()->status);
        $this->assertSame(ProductStatus::HiddenByAdmin, $hidden->fresh()->status);
    }

    public function test_the_list_filters_by_status(): void
    {
        $draft = Product::factory()->create(['store_id' => $this->store->id]);
        $published = Product::factory()->published()->create(['store_id' => $this->store->id]);

        Livewire::test(ListProducts::class)
            ->filterTable('status', 'published')
            ->assertCanSeeTableRecords([$published])
            ->assertCanNotSeeTableRecords([$draft]);
    }

    public function test_the_list_shows_total_stock(): void
    {
        $product = Product::factory()->create(['store_id' => $this->store->id]);
        Variant::factory()->forProduct($product)->stock(2)->create();
        Variant::factory()->forProduct($product)->stock(2)->create();

        Livewire::test(ListProducts::class)
            ->assertTableColumnStateSet('variants_sum_stock', 4, $product);
    }

    public function test_the_dashboard_widgets_count_products_and_list_low_stock_of_this_store_only(): void
    {
        $low = Product::factory()->create(['store_id' => $this->store->id, 'name' => 'Nearly gone']);
        Variant::factory()->forProduct($low)->stock(2)->create();
        $plenty = Product::factory()->create(['store_id' => $this->store->id, 'name' => 'Plenty']);
        Variant::factory()->forProduct($plenty)->stock(50)->create();
        $foreign = Product::factory()->create(['store_id' => $this->otherStore->id, 'name' => 'Foreign low']);
        Variant::factory()->forProduct($foreign)->stock(1)->create();

        Livewire::test(CatalogStatsWidget::class)->assertSee('2');

        Livewire::test(LowStockWidget::class)
            ->assertCanSeeTableRecords([$low])
            ->assertCanNotSeeTableRecords([$plenty, $foreign]);
    }

    public function test_a_platform_admin_cannot_reach_the_vendor_catalog(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->assertContains($this->get('/vendor/store-a/products')->getStatusCode(), [403, 404]);
    }

    public function test_another_stores_product_is_out_of_reach(): void
    {
        $foreign = Product::factory()->create(['store_id' => $this->otherStore->id]);

        $this->assertContains($this->get("/vendor/store-b/products/{$foreign->id}/edit")->getStatusCode(), [403, 404]);
        $this->assertContains($this->get("/vendor/store-a/products/{$foreign->id}/edit")->getStatusCode(), [403, 404]);
    }
}
