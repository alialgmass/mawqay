<?php

namespace Modules\Theming\Tests\Unit;

use Illuminate\Validation\ValidationException;
use Modules\Theming\Exceptions\InvalidThemeManifest;
use Modules\Theming\Support\StyleVariables;
use Modules\Theming\Support\ThemeContract;
use Modules\Theming\Support\ThemeManifest;
use Modules\Theming\Support\ThemeSettingsSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ThemeManifestTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function manifestData(array $overrides = []): array
    {
        return [
            'name' => 'Noor',
            'slug' => 'noor',
            'version' => '1.2.0',
            'contract_version' => '1.0.0',
            'preview' => 'preview.png',
            'pages' => ['Home', 'Category'],
            'settings' => [
                ['key' => 'show_banner', 'type' => 'toggle', 'label' => 'Banner', 'default' => true],
                ['key' => 'columns', 'type' => 'range', 'label' => 'Columns', 'min' => 2, 'max' => 4, 'default' => 3],
                ['key' => 'layout', 'type' => 'select', 'label' => 'Layout', 'options' => ['grid' => 'Grid', 'list' => 'List'], 'default' => 'grid'],
                ['key' => 'accent', 'type' => 'color', 'label' => 'Accent', 'default' => '#112233'],
            ],
            ...$overrides,
        ];
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function invalidManifests(): array
    {
        return [
            'missing name' => [['name' => '']],
            'bad slug' => [['slug' => 'Bad Slug']],
            'bad version' => [['version' => '1.0']],
            'bad contract version' => [['contract_version' => 'latest']],
            'no pages' => [['pages' => []]],
            'unknown page' => [['pages' => ['Home', 'Checkout2']]],
            'setting redefines a core key' => [['settings' => [['key' => 'primary_color', 'type' => 'color', 'label' => 'x', 'default' => '#000000']]]],
            'duplicate setting keys' => [['settings' => [
                ['key' => 'a', 'type' => 'toggle', 'label' => 'x', 'default' => true],
                ['key' => 'a', 'type' => 'toggle', 'label' => 'y', 'default' => false],
            ]]],
            'unknown field type' => [['settings' => [['key' => 'a', 'type' => 'textarea', 'label' => 'x', 'default' => '']]]],
            'colour default not hex' => [['settings' => [['key' => 'a', 'type' => 'color', 'label' => 'x', 'default' => 'red']]]],
            'select default not an option' => [['settings' => [['key' => 'a', 'type' => 'select', 'label' => 'x', 'options' => ['x' => 'X'], 'default' => 'y']]]],
            'range default out of bounds' => [['settings' => [['key' => 'a', 'type' => 'range', 'label' => 'x', 'min' => 1, 'max' => 3, 'default' => 9]]]],
            'range min above max' => [['settings' => [['key' => 'a', 'type' => 'range', 'label' => 'x', 'min' => 5, 'max' => 3, 'default' => 4]]]],
            'toggle default not boolean' => [['settings' => [['key' => 'a', 'type' => 'toggle', 'label' => 'x', 'default' => 'yes']]]],
            'defaults for a non-core key' => [['defaults' => ['accent' => '#000000']]],
            'core default not a colour' => [['defaults' => ['primary_color' => 'blue']]],
            'core font default not approved' => [['defaults' => ['heading_font' => 'comic-sans']]],
        ];
    }

    public function test_a_valid_manifest_is_accepted(): void
    {
        $manifest = ThemeManifest::fromArray($this->manifestData(['defaults' => ['primary_color' => '#101010', 'corner_style' => 'sharp']]));

        $this->assertSame('noor', $manifest->slug);
        $this->assertTrue($manifest->hasPage('Home'));
        $this->assertFalse($manifest->hasPage('Cart'));
        $this->assertTrue($manifest->isSupported());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidManifests')]
    public function test_an_invalid_manifest_is_rejected_with_reasons(array $overrides): void
    {
        try {
            ThemeManifest::fromArray($this->manifestData($overrides));
            $this->fail('The manifest should have been rejected.');
        } catch (InvalidThemeManifest $exception) {
            $this->assertNotEmpty($exception->errors);
        }
    }

    public function test_the_current_and_previous_contract_majors_are_supported(): void
    {
        $this->assertTrue(ThemeContract::supports('1.0.0'));
        $this->assertTrue(ThemeContract::supports('1.7.3'));
        $this->assertTrue(ThemeContract::supports('0.9.0'));
        $this->assertFalse(ThemeContract::supports('2.0.0'));
        $this->assertFalse(ThemeContract::supports('garbage'));
    }

    public function test_settings_defaults_cover_core_and_theme_fields(): void
    {
        $defaults = (new ThemeSettingsSchema(ThemeManifest::fromArray($this->manifestData())))->defaults();

        $this->assertSame('#7A3E65', $defaults['primary_color']);
        $this->assertSame('cairo', $defaults['heading_font']);
        $this->assertSame('soft', $defaults['corner_style']);
        $this->assertTrue($defaults['show_banner']);
        $this->assertSame(3, $defaults['columns']);
    }

    public function test_valid_settings_are_accepted_and_typed(): void
    {
        $clean = (new ThemeSettingsSchema(ThemeManifest::fromArray($this->manifestData())))->validate([
            'primary_color' => '#112233',
            'heading_font' => 'tajawal',
            'corner_style' => 'sharp',
            'show_banner' => '0',
            'columns' => '4',
            'layout' => 'list',
            'secondary_color' => null,
        ]);

        $this->assertSame([
            'primary_color' => '#112233',
            'heading_font' => 'tajawal',
            'corner_style' => 'sharp',
            'show_banner' => false,
            'columns' => 4,
            'layout' => 'list',
        ], $clean);
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function invalidSettings(): array
    {
        return [
            'colour not hex' => [['primary_color' => 'red']],
            'colour without hash' => [['primary_color' => '112233']],
            'font not approved' => [['heading_font' => 'comic-sans']],
            'corner not offered' => [['corner_style' => 'round']],
            'select outside options' => [['layout' => 'masonry']],
            'range above max' => [['columns' => 9]],
            'range below min' => [['columns' => 1]],
            'range not a number' => [['columns' => 'many']],
            'unknown field' => [['spacing' => 12]],
            'a field the contract keeps fixed' => [['color_danger' => '#FF0000']],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    #[DataProvider('invalidSettings')]
    public function test_invalid_settings_are_rejected(array $input): void
    {
        $this->expectException(ValidationException::class);

        (new ThemeSettingsSchema(ThemeManifest::fromArray($this->manifestData())))->validate($input);
    }

    public function test_style_variables_follow_the_settings(): void
    {
        $manifest = ThemeManifest::fromArray($this->manifestData());
        $settings = (new ThemeSettingsSchema($manifest))->defaults();

        $variables = StyleVariables::from($manifest, [
            ...$settings,
            'primary_color' => '#FFD700',
            'secondary_color' => '#1B2A49',
            'heading_font' => 'tajawal',
            'corner_style' => 'sharp',
            'show_banner' => false,
            'columns' => 4,
        ]);

        $this->assertSame('#FFD700', $variables['--color-primary']);
        $this->assertSame('#000000', $variables['--color-on-primary']);
        $this->assertSame('#FFFFFF', $variables['--color-on-secondary']);
        $this->assertSame("'Tajawal', sans-serif", $variables['--font-heading']);
        $this->assertSame("'IBM Plex Sans Arabic', sans-serif", $variables['--font-body']);
        $this->assertSame('0px', $variables['--radius-md']);
        $this->assertSame('0', $variables['--theme-show-banner']);
        $this->assertSame('4', $variables['--theme-columns']);
        $this->assertSame(config('storefront.tokens')['--color-text'], $variables['--color-text']);
    }

    public function test_soft_corners_use_the_larger_radii(): void
    {
        $manifest = ThemeManifest::fromArray($this->manifestData());
        $variables = StyleVariables::from($manifest, [...(new ThemeSettingsSchema($manifest))->defaults(), 'corner_style' => 'soft']);

        $this->assertSame(['6px', '10px', '16px'], [$variables['--radius-sm'], $variables['--radius-md'], $variables['--radius-lg']]);
    }
}
