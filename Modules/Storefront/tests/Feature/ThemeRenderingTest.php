<?php

namespace Modules\Storefront\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Auth\Models\Customer;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductMedia;
use Modules\Catalog\Models\Variant;
use Modules\Core\Support\StoreContext;
use Modules\Storefront\Pages\AccountPageData;
use Modules\Storefront\Pages\AddressesPageData;
use Modules\Storefront\Pages\CartPageData;
use Modules\Storefront\Pages\CategoryPageData;
use Modules\Storefront\Pages\CheckoutPageData;
use Modules\Storefront\Pages\ForgotPasswordPageData;
use Modules\Storefront\Pages\HomePageData;
use Modules\Storefront\Pages\LoginPageData;
use Modules\Storefront\Pages\NotFoundPageData;
use Modules\Storefront\Pages\OrderConfirmationPageData;
use Modules\Storefront\Pages\OrderDetailPageData;
use Modules\Storefront\Pages\OrdersPageData;
use Modules\Storefront\Pages\PageData;
use Modules\Storefront\Pages\ProductPageData;
use Modules\Storefront\Pages\RegisterPageData;
use Modules\Storefront\Pages\ResetPasswordPageData;
use Modules\Storefront\Pages\SearchPageData;
use Modules\Storefront\Pages\SurveyPageData;
use Modules\Tenancy\Models\Store;
use Modules\Theming\Models\Theme;
use Modules\Theming\Support\ThemeContract;
use Modules\Theming\Support\ThemeManager;
use Modules\Theming\Support\ThemeRegistry;
use Modules\Theming\Testing\FakesThemeFolder;
use Tests\TestCase;

class ThemeRenderingTest extends TestCase
{
    use FakesThemeFolder;
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useThemeFolder();
        $this->store = Store::factory()->create(['slug' => 'noor', 'name' => 'Noor']);
    }

    protected function tearDown(): void
    {
        $this->tearDownThemeFolder();

        parent::tearDown();
    }

    private function registerTheme(string $slug, array $overrides = [], ?array $pages = null): Theme
    {
        $this->makeTheme($slug, $overrides, $pages ?? ThemeContract::REQUIRED_PAGES);

        return app(ThemeRegistry::class)->register($slug);
    }

    private function useTheme(Theme $theme, array $settings = []): void
    {
        $this->store->update(['theme_id' => $theme->id, 'theme_settings' => [$theme->slug => $settings]]);
    }

    public function test_a_store_without_a_theme_renders_the_default(): void
    {
        $this->get('/noor')->assertInertia(fn (Assert $page) => $page->component('storefront/default/Home'));
    }

    public function test_a_store_renders_its_chosen_theme(): void
    {
        $this->useTheme($this->registerTheme('noor-theme'));

        $this->get('/noor')->assertInertia(fn (Assert $page) => $page->component('storefront/noor-theme/Home'));
    }

    public function test_a_page_the_theme_lacks_falls_back_to_the_default_for_that_page_only(): void
    {
        $this->useTheme($this->registerTheme('lean', pages: ['Home', 'Category', 'Product', 'Cart', 'Checkout', 'Account', 'Orders', 'NotFound']));
        app(StoreContext::class)->set($this->store->id);
        Category::factory()->create(['store_id' => $this->store->id, 'slug' => 'abayas']);

        $this->get('/noor/search?q=x')->assertInertia(fn (Assert $page) => $page->component('storefront/default/Search'));
        $this->get('/noor/categories/abayas')->assertInertia(fn (Assert $page) => $page->component('storefront/lean/Category'));
    }

    public function test_the_customer_pages_fall_back_to_the_default_when_a_theme_lacks_them(): void
    {
        $this->useTheme($this->registerTheme('lean'));

        foreach (['login' => 'Login', 'register' => 'Register', 'forgot-password' => 'ForgotPassword', 'reset-password/abc' => 'ResetPassword'] as $path => $component) {
            $this->get("/noor/{$path}")->assertInertia(fn (Assert $page) => $page->component("storefront/default/{$component}"));
        }
    }

    public function test_a_theme_that_provides_a_customer_page_is_used_for_it(): void
    {
        $this->useTheme($this->registerTheme('full', pages: [...ThemeContract::REQUIRED_PAGES, 'Login']));

        $this->get('/noor/login')->assertInertia(fn (Assert $page) => $page->component('storefront/full/Login'));
        $this->get('/noor/register')->assertInertia(fn (Assert $page) => $page->component('storefront/default/Register'));
    }

    public function test_a_theme_for_an_unsupported_contract_uses_the_default_everywhere(): void
    {
        $theme = $this->registerTheme('future', ['contract_version' => '2.0.0']);
        $this->useTheme($theme);

        $this->get('/noor')->assertInertia(fn (Assert $page) => $page->component('storefront/default/Home'));
        $this->get('/noor/search')->assertInertia(fn (Assert $page) => $page->component('storefront/default/Search'));
    }

    public function test_the_previous_contract_major_is_still_served(): void
    {
        $this->useTheme($this->registerTheme('older', ['contract_version' => '0.9.0']));

        $this->get('/noor')->assertInertia(fn (Assert $page) => $page->component('storefront/older/Home'));
    }

    public function test_a_deactivated_theme_falls_back_to_the_default_and_returns_when_reactivated(): void
    {
        $theme = $this->registerTheme('noor-theme');
        $this->useTheme($theme);
        $this->get('/noor')->assertInertia(fn (Assert $page) => $page->component('storefront/noor-theme/Home'));

        $theme->update(['is_active' => false]);
        $this->get('/noor')->assertInertia(fn (Assert $page) => $page->component('storefront/default/Home'));

        $theme->update(['is_active' => true]);
        $this->get('/noor')->assertInertia(fn (Assert $page) => $page->component('storefront/noor-theme/Home'));
    }

    public function test_changing_the_stores_theme_is_visible_on_the_next_request(): void
    {
        $first = $this->registerTheme('first');
        $second = $this->registerTheme('second');

        $this->useTheme($first);
        $this->get('/noor')->assertInertia(fn (Assert $page) => $page->component('storefront/first/Home'));

        $this->useTheme($second);
        $this->get('/noor')->assertInertia(fn (Assert $page) => $page->component('storefront/second/Home'));
    }

    public function test_a_themes_new_version_is_picked_up_after_registration_without_stale_cache(): void
    {
        $theme = $this->registerTheme('noor-theme');
        $this->useTheme($theme);
        $this->get('/noor');

        $this->makeTheme('noor-theme', ['contract_version' => '2.0.0']);
        app(ThemeRegistry::class)->register('noor-theme');

        $this->get('/noor')->assertInertia(fn (Assert $page) => $page->component('storefront/default/Home'));
    }

    public function test_saved_settings_become_style_variables(): void
    {
        $this->useTheme($this->registerTheme('noor-theme'), [
            'primary_color' => '#FFD700',
            'secondary_color' => '#1B2A49',
            'heading_font' => 'tajawal',
            'corner_style' => 'sharp',
        ]);

        $this->get('/noor')->assertInertia(fn (Assert $page) => $page
            ->where('tokens.--color-primary', '#FFD700')
            ->where('tokens.--color-on-primary', '#000000')
            ->where('tokens.--color-on-secondary', '#FFFFFF')
            ->where('tokens.--font-heading', "'Tajawal', sans-serif")
            ->where('tokens.--radius-md', '0px'));
    }

    public function test_theme_settings_beat_the_store_identity_colour_which_beats_the_theme_default(): void
    {
        $theme = $this->registerTheme('noor-theme', ['defaults' => ['primary_color' => '#101010']]);
        $this->useTheme($theme);
        $this->get('/noor')->assertInertia(fn (Assert $page) => $page->where('tokens.--color-primary', '#101010'));

        $this->store->update(['primary_color' => '#1B2A49']);
        $this->get('/noor')->assertInertia(fn (Assert $page) => $page->where('tokens.--color-primary', '#1B2A49'));

        $this->useTheme($theme, ['primary_color' => '#7A3E65']);
        $this->get('/noor')->assertInertia(fn (Assert $page) => $page->where('tokens.--color-primary', '#7A3E65'));
    }

    public function test_each_themes_settings_are_remembered_separately(): void
    {
        $first = $this->registerTheme('first');
        $second = $this->registerTheme('second');
        $this->store->update(['theme_id' => $second->id, 'theme_settings' => [
            'first' => ['primary_color' => '#111111'],
            'second' => ['primary_color' => '#222222'],
        ]]);

        $this->get('/noor')->assertInertia(fn (Assert $page) => $page->where('tokens.--color-primary', '#222222'));

        $this->store->update(['theme_id' => $first->id]);
        $this->get('/noor')->assertInertia(fn (Assert $page) => $page->where('tokens.--color-primary', '#111111'));
    }

    public function test_one_stores_theme_never_affects_another(): void
    {
        $this->useTheme($this->registerTheme('noor-theme'), ['primary_color' => '#FFD700']);
        Store::factory()->create(['slug' => 'other']);

        $this->get('/other')->assertInertia(fn (Assert $page) => $page
            ->component('storefront/default/Home')
            ->where('tokens.--color-primary', config('storefront.tokens.--color-primary')));
    }

    public function test_theme_specific_settings_reach_the_page_as_variables(): void
    {
        $this->useTheme($this->registerTheme('noor-theme', ['settings' => [
            ['key' => 'columns', 'type' => 'range', 'label' => 'Columns', 'min' => 2, 'max' => 4, 'default' => 3],
        ]]), ['columns' => 4]);

        $this->get('/noor')->assertInertia(fn (Assert $page) => $page->where('tokens.--theme-columns', '4'));
    }

    public function test_the_owner_previews_an_unpublished_theme_without_changing_the_published_one(): void
    {
        $this->registerTheme('preview-me');
        $owner = User::factory()->vendorOwner($this->store)->create();

        $this->actingAs($owner)->get('/noor?preview_theme=preview-me')
            ->assertInertia(fn (Assert $page) => $page->component('storefront/preview-me/Home'));

        $this->assertNull($this->store->fresh()->theme_id);
        $this->get('/noor')->assertInertia(fn (Assert $page) => $page->component('storefront/default/Home'));
    }

    public function test_a_preview_can_carry_unsaved_settings_that_are_validated_and_never_saved(): void
    {
        $this->registerTheme('preview-me');
        $owner = User::factory()->vendorOwner($this->store)->create();

        $this->actingAs($owner)
            ->get('/noor?'.http_build_query(['preview_theme' => 'preview-me', 'preview_settings' => ['primary_color' => '#FFD700']]))
            ->assertInertia(fn (Assert $page) => $page->where('tokens.--color-primary', '#FFD700'));

        $this->actingAs($owner)
            ->get('/noor?'.http_build_query(['preview_theme' => 'preview-me', 'preview_settings' => ['primary_color' => 'javascript:alert(1)']]))
            ->assertInertia(fn (Assert $page) => $page->where('tokens.--color-primary', config('storefront.tokens.--color-primary')));

        $this->assertNull($this->store->fresh()->theme_settings);
    }

    public function test_preview_is_ignored_for_everyone_but_the_stores_own_owner(): void
    {
        $this->registerTheme('preview-me');
        $query = '/noor?preview_theme=preview-me';
        $otherStore = Store::factory()->create(['slug' => 'other']);

        $this->get($query)->assertInertia(fn (Assert $page) => $page->component('storefront/default/Home'));

        $this->actingAs(User::factory()->vendorOwner($otherStore)->create())->get($query)
            ->assertInertia(fn (Assert $page) => $page->component('storefront/default/Home'));

        $this->actingAs(User::factory()->admin()->create())->get($query)
            ->assertInertia(fn (Assert $page) => $page->component('storefront/default/Home'));
    }

    public function test_preview_is_ignored_for_a_customer(): void
    {
        $this->registerTheme('preview-me');
        $customer = Customer::factory()->create(['store_id' => $this->store->id]);

        $this->actingAs($customer, 'customer')->get('/noor?preview_theme=preview-me')
            ->assertInertia(fn (Assert $page) => $page->component('storefront/default/Home'));
    }

    public function test_a_theme_whose_folder_has_vanished_falls_back_to_the_default(): void
    {
        $theme = $this->registerTheme('gone');
        $this->useTheme($theme);
        $this->get('/noor')->assertInertia(fn (Assert $page) => $page->component('storefront/gone/Home'));

        File::deleteDirectory("{$this->themeFolder}/gone");
        ThemeManager::flush();

        $this->get('/noor')->assertInertia(fn (Assert $page) => $page->component('storefront/default/Home'));
    }

    public function test_preview_cannot_show_an_inactive_or_unregistered_theme(): void
    {
        $this->registerTheme('off')->update(['is_active' => false]);
        $this->makeTheme('unregistered');
        $this->actingAs(User::factory()->vendorOwner($this->store)->create());

        foreach (['off', 'unregistered', '../etc', 'nope'] as $slug) {
            $this->get('/noor?preview_theme='.$slug)
                ->assertInertia(fn (Assert $page) => $page->component('storefront/default/Home'));
        }
    }

    public function test_an_unknown_address_inside_a_store_shows_the_themed_not_found_page(): void
    {
        $this->useTheme($this->registerTheme('noor-theme'));

        $this->get('/noor/no/such/page')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page->component('storefront/noor-theme/NotFound'));
    }

    public function test_a_missing_product_or_category_shows_the_stores_not_found_page(): void
    {
        $this->get('/noor/products/nothing')->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page->component('storefront/default/NotFound')->where('store.name', 'Noor'));
        $this->get('/noor/categories/nothing')->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page->component('storefront/default/NotFound'));
    }

    public function test_platform_addresses_are_not_read_as_a_store_page(): void
    {
        app(ThemeRegistry::class)->register('default');

        $this->get('/theme-previews/default')->assertOk();
        $this->get('/vendor/noor')->assertRedirect();
    }

    public function test_every_page_data_object_carries_the_contract_version_and_only_plain_values(): void
    {
        $shell = ['store' => [], 'tokens' => []];
        $seo = ['title' => 't', 'description' => null, 'image' => null];

        $pages = [
            new HomePageData($shell, $seo, [], []),
            new CategoryPageData($shell, $seo, ['name' => 'a', 'slug' => 'a'], [], 'newest', []),
            new ProductPageData($shell, $seo, [], []),
            new SearchPageData($shell, $seo, 'q', [], []),
            new CartPageData($shell, $seo, [], '0 EGP', false, false, true),
            new CheckoutPageData($shell, $seo, false, [], '0 EGP', [], [], [], 10, []),
            new AccountPageData($shell, $seo, null),
            new OrdersPageData($shell, $seo, []),
            new OrderDetailPageData($shell, $seo, []),
            new OrderConfirmationPageData($shell, $seo, []),
            new LoginPageData($shell, $seo, null),
            new RegisterPageData($shell, $seo, null),
            new ForgotPasswordPageData($shell, $seo),
            new ResetPasswordPageData($shell, $seo, 't', 'e'),
            new AddressesPageData($shell, $seo, [], [], 10),
            new SurveyPageData($shell, $seo, 'invalid'),
            new NotFoundPageData($shell, $seo),
        ];

        $this->assertEqualsCanonicalizing(
            ThemeContract::knownPages(),
            array_map(fn (PageData $page): string => $page::page(), $pages),
        );

        foreach ($pages as $page) {
            $this->assertSame(ThemeContract::VERSION, $page->toArray()['contractVersion']);
            $this->assertIsArray(json_decode(json_encode($page->toArray()), true));
        }
    }

    public function test_real_pages_carry_the_contract_version_and_no_model_objects(): void
    {
        app(StoreContext::class)->set($this->store->id);
        $category = Category::factory()->create(['store_id' => $this->store->id, 'slug' => 'abayas']);
        $product = Product::factory()->published()->create(['store_id' => $this->store->id, 'category_id' => $category->id, 'slug' => 'p']);
        Variant::factory()->forProduct($product)->create();
        ProductMedia::factory()->forProduct($product)->create();

        foreach (['/noor', '/noor/categories/abayas', '/noor/products/p', '/noor/search?q=p', '/noor/nowhere'] as $url) {
            $response = $this->get($url);
            $props = collect($response->viewData('page')['props'])->except('errors')->all();

            $this->assertSame(ThemeContract::VERSION, $props['contractVersion'], $url);
            $this->assertSame($props, json_decode(json_encode($props), true), $url);
        }
    }

    public function test_the_cache_keeps_the_theme_per_store(): void
    {
        $this->useTheme($this->registerTheme('noor-theme'));
        $this->get('/noor');

        $this->assertNotNull(Cache::get("store:{$this->store->id}:theme"));
    }
}
