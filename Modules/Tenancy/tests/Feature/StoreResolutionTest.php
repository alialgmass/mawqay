<?php

namespace Modules\Tenancy\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Tenancy\Contracts\StoreResolver;
use Modules\Tenancy\Models\Store;
use Tests\TestCase;

class StoreResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_known_slug_shows_that_stores_name(): void
    {
        Store::factory()->create(['slug' => 'noor-hijab', 'name' => 'Noor Hijab']);
        Store::factory()->create(['slug' => 'other-shop', 'name' => 'Other Shop']);

        $this->get('/noor-hijab')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('storefront/Home')
                ->where('store.name', 'Noor Hijab')
                ->where('store.slug', 'noor-hijab'));
    }

    public function test_an_unknown_slug_returns_a_not_found_page(): void
    {
        $this->get('/no-such-store')->assertNotFound();
    }

    public function test_the_page_is_arabic_and_right_to_left(): void
    {
        Store::factory()->create(['slug' => 'noor-hijab']);

        $this->get('/noor-hijab')
            ->assertSee('lang="ar"', false)
            ->assertSee('dir="rtl"', false);
    }

    public function test_the_default_design_tokens_are_provided(): void
    {
        Store::factory()->create(['slug' => 'noor-hijab']);

        $this->get('/noor-hijab')
            ->assertInertia(fn (Assert $page) => $page
                ->has('tokens.--color-primary')
                ->has('tokens.--color-secondary')
                ->has('tokens.--color-bg')
                ->has('tokens.--color-surface')
                ->has('tokens.--color-text')
                ->has('tokens.--color-text-muted')
                ->has('tokens.--color-border')
                ->has('tokens.--radius-md')
                ->has('tokens.--font-body')
                ->has('tokens.--font-heading'));
    }

    public function test_the_store_resolver_can_be_replaced_without_touching_other_code(): void
    {
        $fixed = Store::factory()->create(['slug' => 'fixed-store', 'name' => 'Fixed Store']);

        $this->app->bind(StoreResolver::class, fn () => new class($fixed) implements StoreResolver
        {
            public function __construct(private readonly Store $store) {}

            public function resolve(Request $request): ?Store
            {
                return $this->store;
            }
        });

        $this->get('/whatever-slug')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('store.name', 'Fixed Store'));
    }
}
