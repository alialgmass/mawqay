<?php

namespace Modules\Catalog\Tests\Feature;

use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Filament\Admin\Resources\Categories\CategoryResource;
use Modules\Catalog\Filament\Admin\Resources\Categories\Pages\ListCategories;
use Modules\Catalog\Filament\Admin\Resources\Products\Pages\ListProducts;
use Modules\Catalog\Filament\Admin\Resources\Products\ProductResource;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class AdminCatalogModerationTest extends TestCase
{
    use RefreshDatabase;

    private Store $storeA;

    private Store $storeB;

    private Product $productA;

    private Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = Store::factory()->create(['name' => 'Alpha']);
        $this->storeB = Store::factory()->create(['name' => 'Beta']);
        $this->productA = Product::factory()->published()->create(['store_id' => $this->storeA->id, 'name' => 'Alpha abaya']);
        $this->productB = Product::factory()->published()->create(['store_id' => $this->storeB->id, 'name' => 'Beta scarf']);

        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel('admin');
    }

    public function test_the_admin_sees_products_and_categories_of_every_store(): void
    {
        $categoryA = Category::query()->withoutGlobalScopes()->findOrFail($this->productA->category_id);
        $categoryB = Category::query()->withoutGlobalScopes()->findOrFail($this->productB->category_id);

        Livewire::test(ListProducts::class)
            ->assertCanSeeTableRecords([$this->productA, $this->productB])
            ->assertTableColumnStateSet('category.name', $categoryA->name, $this->productA)
            ->assertTableColumnStateSet('store.name', 'Alpha', $this->productA);

        Livewire::test(ListCategories::class)
            ->assertCanSeeTableRecords([$categoryA, $categoryB]);
    }

    public function test_the_admin_filters_by_store(): void
    {
        Livewire::test(ListProducts::class)
            ->filterTable('store_id', $this->storeA->id)
            ->assertCanSeeTableRecords([$this->productA])
            ->assertCanNotSeeTableRecords([$this->productB]);
    }

    public function test_the_admin_searches_by_name(): void
    {
        Livewire::test(ListProducts::class)
            ->searchTable('scarf')
            ->assertCanSeeTableRecords([$this->productB])
            ->assertCanNotSeeTableRecords([$this->productA]);
    }

    public function test_the_admin_filters_by_status(): void
    {
        $draft = Product::factory()->create(['store_id' => $this->storeA->id]);

        Livewire::test(ListProducts::class)
            ->filterTable('status', 'draft')
            ->assertCanSeeTableRecords([$draft])
            ->assertCanNotSeeTableRecords([$this->productA]);
    }

    public function test_the_admin_hides_a_product_with_a_reason_and_restores_it(): void
    {
        Livewire::test(ListProducts::class)
            ->callAction(TestAction::make('hide')->table($this->productA), ['reason' => 'Counterfeit goods'])
            ->assertHasNoActionErrors();

        $hidden = Product::query()->withoutGlobalScopes()->find($this->productA->id);
        $this->assertSame(ProductStatus::HiddenByAdmin, $hidden->status);
        $this->assertSame('Counterfeit goods', $hidden->hide_reason);

        Livewire::test(ListProducts::class)
            ->callAction(TestAction::make('restore')->table($this->productA));

        $this->assertSame(ProductStatus::Draft, Product::query()->withoutGlobalScopes()->find($this->productA->id)->status);
    }

    public function test_hiding_requires_a_reason(): void
    {
        Livewire::test(ListProducts::class)
            ->callAction(TestAction::make('hide')->table($this->productA), ['reason' => ''])
            ->assertHasActionErrors(['reason' => 'required']);

        $this->assertSame(ProductStatus::Published, Product::query()->withoutGlobalScopes()->find($this->productA->id)->status);
    }

    public function test_the_admin_cannot_create_products_or_categories(): void
    {
        Livewire::test(ListProducts::class)->assertActionDoesNotExist('create');
        $this->assertFalse(ProductResource::canCreate());
        $this->assertFalse(CategoryResource::canCreate());
        $this->assertSame(1, Category::query()->withoutGlobalScopes()->where('store_id', $this->storeA->id)->count());
    }

    public function test_a_vendor_owner_cannot_open_the_admin_catalog(): void
    {
        $this->actingAs(User::factory()->vendorOwner($this->storeA)->create());

        $this->get('/admin/products')->assertForbidden();
    }
}
