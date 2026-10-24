<?php

namespace Modules\Tenancy\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Tenancy\Actions\ChangeStoreSlug;
use Modules\Tenancy\Actions\ChangeStoreStatus;
use Modules\Tenancy\Enums\StoreStatus;
use Modules\Tenancy\Models\Store;
use RuntimeException;
use Tests\TestCase;

class StoreVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_active_store_is_served_normally(): void
    {
        Store::factory()->create(['slug' => 'live-shop']);

        $this->get('/live-shop')->assertOk();
    }

    public function test_a_draft_store_looks_missing_to_guests_and_other_users(): void
    {
        $store = Store::factory()->draft()->create(['slug' => 'draft-shop']);
        $otherOwner = User::factory()->vendorOwner(Store::factory()->create())->create();

        $this->get('/draft-shop')->assertNotFound();
        $this->actingAs($otherOwner)->get('/draft-shop')->assertNotFound();
        $this->assertSame(StoreStatus::Draft, $store->fresh()->status);
    }

    public function test_a_draft_store_can_be_previewed_by_its_own_owner_and_by_a_platform_admin(): void
    {
        $store = Store::factory()->draft()->create(['slug' => 'draft-shop']);

        $this->actingAs(User::factory()->vendorOwner($store)->create())->get('/draft-shop')->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get('/draft-shop')->assertOk();
    }

    public function test_a_suspended_store_shows_a_maintenance_page_with_a_503(): void
    {
        Store::factory()->suspended()->create(['slug' => 'paused-shop', 'name' => 'Paused Shop']);

        $this->get('/paused-shop')
            ->assertStatus(503)
            ->assertSee('Paused Shop')
            ->assertSee('dir="rtl"', false)
            ->assertHeader('Retry-After');
    }

    public function test_an_unknown_store_shows_the_arabic_not_found_page(): void
    {
        $this->get('/no-such-shop')
            ->assertNotFound()
            ->assertSee('lang="ar"', false)
            ->assertSee(__('tenancy::pages.not_found_title', [], 'ar'));
    }

    public function test_only_active_stores_can_trade(): void
    {
        $this->assertTrue(Store::factory()->create()->canTrade());
        $this->assertFalse(Store::factory()->draft()->create()->canTrade());
        $this->assertFalse(Store::factory()->suspended()->create()->canTrade());
    }

    public function test_the_status_flow_allows_only_the_permitted_moves(): void
    {
        $change = app(ChangeStoreStatus::class);
        $store = Store::factory()->draft()->create();

        $this->assertSame(StoreStatus::Active, $change($store, StoreStatus::Active)->status);
        $this->assertSame(StoreStatus::Suspended, $change($store, StoreStatus::Suspended)->status);
        $this->assertSame(StoreStatus::Active, $change($store, StoreStatus::Active)->status);
    }

    public function test_a_store_cannot_return_to_draft_or_skip_activation(): void
    {
        $change = app(ChangeStoreStatus::class);

        try {
            $change(Store::factory()->create(), StoreStatus::Draft);
            $this->fail('Active to Draft must be refused.');
        } catch (RuntimeException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(RuntimeException::class);
        $change(Store::factory()->draft()->create(), StoreStatus::Suspended);
    }

    public function test_changing_a_slug_keeps_the_old_address_redirecting_permanently(): void
    {
        $store = Store::factory()->create(['slug' => 'old-name']);

        app(ChangeStoreSlug::class)($store, 'new-name');

        $this->get('/new-name')->assertOk();
        $this->get('/old-name')->assertStatus(301)->assertRedirect('/new-name');
    }

    public function test_the_redirect_keeps_the_rest_of_the_path_and_query(): void
    {
        $store = Store::factory()->create(['slug' => 'old-name']);
        app(ChangeStoreSlug::class)($store, 'new-name');

        $this->post('/old-name/customer/login', [])->assertStatus(301);
        $this->get('/old-name/?ref=ad')->assertStatus(301)->assertRedirect('/new-name/?ref=ad');
    }

    public function test_a_second_rename_still_sends_every_old_address_to_the_current_one(): void
    {
        $store = Store::factory()->create(['slug' => 'first-name']);
        $change = app(ChangeStoreSlug::class);

        $change($store, 'second-name');
        $change($store, 'third-name');

        $this->get('/first-name')->assertRedirect('/third-name');
        $this->get('/second-name')->assertRedirect('/third-name');
    }

    public function test_a_store_cannot_take_back_one_of_its_own_old_slugs(): void
    {
        $store = Store::factory()->create(['slug' => 'first-name']);
        $change = app(ChangeStoreSlug::class);

        $change($store, 'second-name');

        $this->expectException(ValidationException::class);
        $change($store, 'first-name');
    }

    public function test_a_draft_stores_old_address_does_not_reveal_the_new_one(): void
    {
        $store = Store::factory()->create(['slug' => 'old-name']);
        app(ChangeStoreSlug::class)($store, 'new-name');
        $store->update(['status' => StoreStatus::Draft]);

        $this->get('/old-name')->assertNotFound();
    }

    public function test_a_bad_stored_colour_does_not_break_the_storefront(): void
    {
        Store::factory()->create(['slug' => 'odd-shop', 'primary_color' => 'red', 'secondary_color' => '#abc']);

        $this->get('/odd-shop')->assertOk();
    }

    public function test_a_slug_that_is_invalid_reserved_taken_or_anothers_old_address_is_refused(): void
    {
        $store = Store::factory()->create(['slug' => 'my-shop']);
        $other = Store::factory()->create(['slug' => 'other-shop']);
        app(ChangeStoreSlug::class)($other, 'other-renamed');

        foreach (['x', 'Has Caps', 'admin', str_repeat('a', 31), 'other-renamed', 'other-shop'] as $bad) {
            try {
                app(ChangeStoreSlug::class)($store, $bad);
                $this->fail("Slug [{$bad}] should have been refused.");
            } catch (ValidationException) {
                $this->assertSame('my-shop', $store->fresh()->slug);
            }
        }
    }
}
