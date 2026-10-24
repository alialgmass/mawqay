<?php

namespace Modules\Theming\Tests\Feature;

use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Core\Support\StoreContext;
use Modules\Tenancy\Models\Store;
use Modules\Theming\Filament\Admin\Resources\Themes\Pages\ListThemes;
use Modules\Theming\Filament\Vendor\Pages\Appearance;
use Modules\Theming\Models\Theme;
use Modules\Theming\Support\ThemeRegistry;
use Modules\Theming\Testing\FakesThemeFolder;
use Tests\TestCase;

class ThemePanelsTest extends TestCase
{
    use FakesThemeFolder;
    use RefreshDatabase;

    private Store $store;

    private Store $otherStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useThemeFolder();
        app(ThemeRegistry::class)->register('default');
        $this->makeTheme('noor', ['settings' => [
            ['key' => 'columns', 'type' => 'range', 'label' => 'Columns', 'min' => 2, 'max' => 4, 'default' => 3],
            ['key' => 'show_banner', 'type' => 'toggle', 'label' => 'Banner', 'default' => true],
        ]]);
        app(ThemeRegistry::class)->register('noor');

        $this->store = Store::factory()->create(['slug' => 'store-a']);
        $this->otherStore = Store::factory()->create(['slug' => 'store-b']);
    }

    protected function tearDown(): void
    {
        $this->tearDownThemeFolder();

        parent::tearDown();
    }

    private function actAsAdmin(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel('admin');
    }

    private function actAsOwner(): void
    {
        $this->actingAs(User::factory()->vendorOwner($this->store)->create());
        Filament::setCurrentPanel('vendor');
        Filament::setTenant($this->store);
        app(StoreContext::class)->set($this->store->id);
    }

    public function test_the_admin_lists_themes_and_switches_them_on_and_off(): void
    {
        $this->actAsAdmin();
        $noor = Theme::query()->where('slug', 'noor')->firstOrFail();

        Livewire::test(ListThemes::class)
            ->assertCanSeeTableRecords(Theme::all())
            ->callAction(TestAction::make('deactivate')->table($noor));
        $this->assertFalse($noor->fresh()->is_active);

        Livewire::test(ListThemes::class)->callAction(TestAction::make('activate')->table($noor));
        $this->assertTrue($noor->fresh()->is_active);
    }

    public function test_the_default_theme_cannot_be_deactivated(): void
    {
        $this->actAsAdmin();
        $default = Theme::query()->where('slug', 'default')->firstOrFail();

        Livewire::test(ListThemes::class)->callAction(TestAction::make('deactivate')->table($default));

        $this->assertTrue($default->fresh()->is_active);
    }

    public function test_rescanning_registers_new_and_upgraded_themes(): void
    {
        $this->actAsAdmin();
        $this->makeTheme('fresh');
        $this->makeTheme('noor', ['version' => '1.5.0']);

        Livewire::test(ListThemes::class)->callAction(TestAction::make('rescan')->table());

        $this->assertDatabaseHas('themes', ['slug' => 'fresh']);
        $this->assertSame('1.5.0', Theme::query()->where('slug', 'noor')->firstOrFail()->version);
    }

    public function test_rescanning_skips_an_invalid_theme_and_keeps_going(): void
    {
        $this->actAsAdmin();
        $this->makeTheme('bad', ['version' => 'x']);
        $this->makeTheme('good');

        Livewire::test(ListThemes::class)->callAction(TestAction::make('rescan')->table());

        $this->assertDatabaseMissing('themes', ['slug' => 'bad']);
        $this->assertDatabaseHas('themes', ['slug' => 'good']);
    }

    public function test_the_theme_list_is_for_platform_admins_only(): void
    {
        $this->actingAs(User::factory()->vendorOwner($this->store)->create());

        $this->get('/admin/themes')->assertForbidden();
    }

    public function test_the_appearance_page_lists_active_themes_and_marks_the_live_one(): void
    {
        $this->actAsOwner();
        Theme::query()->where('slug', 'noor')->update(['is_active' => false]);
        $this->makeTheme('other');
        app(ThemeRegistry::class)->register('other');

        $page = Livewire::test(Appearance::class);
        $themes = collect($page->instance()->themes());

        $this->assertEqualsCanonicalizing(['default', 'other'], $themes->pluck('slug')->all());
        $this->assertSame(['default'], $themes->where('live', true)->pluck('slug')->all());
        $page->assertSee('data-live-theme', false);
    }

    public function test_the_settings_form_is_generated_from_the_chosen_themes_schema(): void
    {
        $this->actAsOwner();

        Livewire::test(Appearance::class)
            ->assertFormFieldDoesNotExist('settings.columns')
            ->call('chooseTheme', 'noor')
            ->assertFormFieldExists('settings.primary_color')
            ->assertFormFieldExists('settings.heading_font')
            ->assertFormFieldExists('settings.corner_style')
            ->assertFormFieldExists('settings.columns')
            ->assertFormFieldExists('settings.show_banner');
    }

    public function test_publishing_changes_only_this_stores_theme_and_settings(): void
    {
        $this->actAsOwner();
        $noor = Theme::query()->where('slug', 'noor')->firstOrFail();

        Livewire::test(Appearance::class)
            ->call('chooseTheme', 'noor')
            ->fillForm(['settings' => ['primary_color' => '#1B2A49', 'columns' => 4, 'show_banner' => false, 'corner_style' => 'sharp']])
            ->call('publish')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $store = $this->store->fresh();
        $this->assertSame($noor->id, $store->theme_id);
        $this->assertSame('#1B2A49', $store->theme_settings['noor']['primary_color']);
        $this->assertSame(4, $store->theme_settings['noor']['columns']);
        $this->assertFalse($store->theme_settings['noor']['show_banner']);
        $this->assertNull($this->otherStore->fresh()->theme_id);

        $this->get('/store-a')->assertInertia(fn ($page) => $page
            ->component('storefront/noor/Home')
            ->where('tokens.--color-primary', '#1B2A49')
            ->where('tokens.--radius-md', '0px')
            ->where('tokens.--theme-columns', '4'));
    }

    public function test_invalid_settings_are_not_published(): void
    {
        $this->actAsOwner();

        Livewire::test(Appearance::class)
            ->call('chooseTheme', 'noor')
            ->fillForm(['settings' => ['primary_color' => 'red', 'columns' => 99, 'heading_font' => 'comic-sans']])
            ->call('publish')
            ->assertHasFormErrors();

        $this->assertNull($this->store->fresh()->theme_id);
        $this->assertNull($this->store->fresh()->theme_settings);
    }

    public function test_choosing_an_inactive_or_unknown_theme_is_refused(): void
    {
        $this->actAsOwner();
        Theme::query()->where('slug', 'noor')->update(['is_active' => false]);

        Livewire::test(Appearance::class)->call('chooseTheme', 'noor')->assertNotFound();
        Livewire::test(Appearance::class)->call('chooseTheme', 'nope')->assertNotFound();
    }

    public function test_settings_for_a_theme_are_remembered_when_switching_away_and_back(): void
    {
        $this->actAsOwner();

        Livewire::test(Appearance::class)
            ->call('chooseTheme', 'noor')
            ->fillForm(['settings' => ['columns' => 2]])
            ->call('publish');

        Livewire::test(Appearance::class)
            ->call('chooseTheme', 'default')
            ->fillForm(['settings' => ['primary_color' => '#111111']])
            ->call('publish')
            ->call('chooseTheme', 'noor')
            ->assertFormSet(['settings.columns' => 2]);

        $this->assertSame(2, $this->store->fresh()->theme_settings['noor']['columns']);
    }

    public function test_the_preview_link_targets_the_chosen_theme_and_current_values_without_saving(): void
    {
        $this->actAsOwner();

        $page = Livewire::test(Appearance::class)
            ->call('chooseTheme', 'noor')
            ->fillForm(['settings' => ['primary_color' => '#FFD700']]);

        $url = $page->instance()->previewUrl();

        $this->assertStringContainsString('/store-a?', $url);
        $this->assertStringContainsString('preview_theme=noor', $url);
        $this->assertStringContainsString(urlencode('#FFD700'), $url);
        $this->assertNull($this->store->fresh()->theme_id);
    }

    public function test_a_poor_primary_colour_shows_a_contrast_warning(): void
    {
        $this->actAsOwner();

        Livewire::test(Appearance::class)
            ->fillForm(['settings' => ['primary_color' => '#FFD700']])
            ->assertSee(__('theming::appearance.contrast_warning'));
    }

    public function test_a_platform_admin_cannot_use_the_vendor_appearance_page(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/vendor/store-a/appearance')->assertForbidden();
    }

    public function test_a_vendor_cannot_reach_another_stores_appearance_page(): void
    {
        $this->actingAs(User::factory()->vendorOwner($this->store)->create());

        $this->assertContains($this->get('/vendor/store-b/appearance')->getStatusCode(), [403, 404]);
    }
}
