<?php

namespace Modules\Theming\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Modules\Theming\Models\Theme;
use Modules\Theming\Testing\FakesThemeFolder;
use Tests\TestCase;

class ThemeRegistrationTest extends TestCase
{
    use FakesThemeFolder;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useThemeFolder();
    }

    protected function tearDown(): void
    {
        $this->tearDownThemeFolder();

        parent::tearDown();
    }

    public function test_registering_a_valid_theme_makes_it_available(): void
    {
        $this->makeTheme('noor', ['version' => '2.1.0']);

        $this->artisan('theme:register', ['slug' => 'noor'])->assertSuccessful();

        $theme = Theme::query()->where('slug', 'noor')->firstOrFail();
        $this->assertTrue($theme->is_active);
        $this->assertSame('2.1.0', $theme->version);
        $this->assertSame('1.0.0', $theme->manifest['contract_version']);
    }

    public function test_an_invalid_manifest_is_rejected_with_reasons_and_nothing_is_stored(): void
    {
        $this->makeTheme('broken', ['version' => 'one']);

        $this->artisan('theme:register', ['slug' => 'broken'])
            ->expectsOutputToContain('semantic version')
            ->assertFailed();

        $this->assertDatabaseMissing('themes', ['slug' => 'broken']);
    }

    public function test_a_theme_must_provide_every_page_it_declares(): void
    {
        $folder = $this->makeTheme('gappy');
        File::delete("{$folder}/pages/Cart.vue");

        $this->artisan('theme:register', ['slug' => 'gappy'])
            ->expectsOutputToContain('pages/Cart.vue does not exist')
            ->assertFailed();
    }

    public function test_the_preview_image_and_folder_name_must_match_the_manifest(): void
    {
        $folder = $this->makeTheme('pretty');
        File::delete("{$folder}/preview.png");
        $this->artisan('theme:register', ['slug' => 'pretty'])->expectsOutputToContain('preview image')->assertFailed();

        $this->makeTheme('other', ['slug' => 'different']);
        $this->artisan('theme:register', ['slug' => 'other'])->expectsOutputToContain('must match its folder name')->assertFailed();
    }

    public function test_an_unknown_folder_or_a_path_trick_is_refused(): void
    {
        $this->artisan('theme:register', ['slug' => 'ghost'])->assertFailed();
        $this->artisan('theme:register', ['slug' => '../secrets'])->assertFailed();
    }

    public function test_the_default_theme_must_be_complete(): void
    {
        File::deleteDirectory("{$this->themeFolder}/default");
        $this->makeTheme('default', [], ['Home']);

        $this->artisan('theme:register', ['slug' => 'default'])
            ->expectsOutputToContain('must provide the "Cart" page')
            ->assertFailed();
    }

    public function test_registering_twice_changes_nothing_and_keeps_the_active_flag(): void
    {
        $this->makeTheme('noor');
        $this->artisan('theme:register', ['slug' => 'noor'])->assertSuccessful();
        Theme::query()->where('slug', 'noor')->update(['is_active' => false]);

        $this->artisan('theme:register', ['slug' => 'noor'])->assertSuccessful();

        $this->assertSame(1, Theme::query()->where('slug', 'noor')->count());
        $this->assertFalse(Theme::query()->where('slug', 'noor')->firstOrFail()->is_active);
    }

    public function test_registering_again_picks_up_a_new_version(): void
    {
        $this->makeTheme('noor');
        $this->artisan('theme:register', ['slug' => 'noor']);

        $this->makeTheme('noor', ['version' => '1.1.0']);
        $this->artisan('theme:register', ['slug' => 'noor']);

        $this->assertSame('1.1.0', Theme::query()->where('slug', 'noor')->firstOrFail()->version);
    }

    public function test_list_shows_registered_themes(): void
    {
        $this->makeTheme('noor');
        $this->artisan('theme:register', ['slug' => 'noor']);

        $this->artisan('theme:list')->expectsOutputToContain('noor')->assertSuccessful();
    }
}
