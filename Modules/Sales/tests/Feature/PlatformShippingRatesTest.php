<?php

namespace Modules\Sales\Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Core\Support\StoreContext;
use Modules\Sales\Actions\SavePlatformShippingRates;
use Modules\Sales\Filament\Admin\Pages\PlatformShippingRates;
use Modules\Sales\Models\PlatformShippingRate;
use Modules\Sales\Models\ShippingRate;
use Modules\Sales\Services\ShippingFeeResolver;
use Modules\Sales\Testing\BuildsShop;
use Tests\TestCase;

class PlatformShippingRatesTest extends TestCase
{
    use BuildsShop;
    use RefreshDatabase;

    private function resolve(string $governorate, ?int $storeId = null): ?int
    {
        $resolver = fn (): ?int => app(ShippingFeeResolver::class)->resolve($governorate);

        return $storeId === null ? $resolver() : app(StoreContext::class)->runAs($storeId, $resolver);
    }

    public function test_the_platforms_governorate_fee_beats_its_flat_fee(): void
    {
        $store = $this->shop('store-a', ['name' => 'Alpha']);
        app(SavePlatformShippingRates::class)(4000, ['cairo' => 6000]);

        $this->assertSame(6000, $this->resolve('cairo', $store->id));
        $this->assertSame(4000, $this->resolve('giza', $store->id));
    }

    public function test_platform_fees_apply_to_a_store_that_set_none(): void
    {
        $store = $this->shop('store-b', ['name' => 'Beta']);
        app(SavePlatformShippingRates::class)(5000, []);

        $this->assertSame(5000, $this->resolve('alexandria', $store->id));
    }

    public function test_platform_fees_win_over_a_stores_old_rates(): void
    {
        $store = $this->shop('store-c', ['name' => 'Gamma']);
        $this->shippingRates($store, 9900);
        app(SavePlatformShippingRates::class)(5000, []);

        $this->assertSame(5000, $this->resolve('cairo', $store->id));
    }

    public function test_a_stores_old_rates_still_apply_until_the_platform_sets_its_own(): void
    {
        $store = $this->shop('store-d', ['name' => 'Delta']);
        $this->shippingRates($store, 9900);

        $this->assertSame(9900, $this->resolve('cairo', $store->id));
    }

    public function test_nobody_delivering_means_no_fee(): void
    {
        $store = $this->shop('store-e', ['name' => 'Epsilon']);

        $this->assertNull($this->resolve('cairo', $store->id));
        $this->assertFalse(app(StoreContext::class)->runAs($store->id, fn (): bool => app(ShippingFeeResolver::class)->hasAnyRate()));
    }

    public function test_saving_replaces_the_previous_platform_rates(): void
    {
        app(SavePlatformShippingRates::class)(5000, ['cairo' => 6000]);
        app(SavePlatformShippingRates::class)(null, ['giza' => 7000]);

        $this->assertSame(1, PlatformShippingRate::query()->count());
        $this->assertSame(7000, PlatformShippingRate::query()->firstOrFail()->fee);
    }

    public function test_negative_or_unknown_fees_are_refused(): void
    {
        $this->expectException(ValidationException::class);

        app(SavePlatformShippingRates::class)(-1, []);
    }

    public function test_an_unknown_governorate_is_refused(): void
    {
        $this->expectException(ValidationException::class);

        app(SavePlatformShippingRates::class)(null, ['atlantis' => 1000]);
    }

    public function test_an_admin_saves_fees_from_the_panel(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel('admin');

        Livewire::test(PlatformShippingRates::class)
            ->fillForm(['flat_fee' => 50, 'rates' => [['governorate' => 'cairo', 'fee' => 70]]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(5000, PlatformShippingRate::query()->where('kind', 'flat')->firstOrFail()->fee);
        $this->assertSame(7000, PlatformShippingRate::query()->where('governorate', 'cairo')->firstOrFail()->fee);
    }

    public function test_a_vendor_cannot_open_the_platform_fees_page(): void
    {
        $store = $this->shop('store-f', ['name' => 'Zeta']);
        $this->actingAs(User::factory()->vendorOwner($store)->create());
        Filament::setCurrentPanel('admin');

        $this->assertFalse(PlatformShippingRates::canAccess());
        $this->assertSame(0, ShippingRate::query()->withoutGlobalScopes()->where('store_id', '!=', $store->id)->count());
    }
}
