<?php

namespace Modules\Theming\Support;

use Illuminate\Support\Str;
use Modules\Core\Support\ColorContrast;

/**
 * Turns resolved Theme settings into the CSS variables the Storefront sets on
 * its root. Themes style themselves with these variables and nothing else.
 */
final class StyleVariables
{
    /**
     * @param  array<string, mixed>  $settings  resolved settings, every schema key present
     * @return array<string, string>
     */
    public static function from(ThemeManifest $manifest, array $settings): array
    {
        $variables = config('storefront.tokens');

        $variables['--color-primary'] = $settings['primary_color'];
        $variables['--color-secondary'] = $settings['secondary_color'];
        $variables['--color-on-primary'] = ColorContrast::readableTextOn($settings['primary_color']);
        $variables['--color-on-secondary'] = ColorContrast::readableTextOn($settings['secondary_color']);
        $variables['--font-heading'] = self::fontStack($settings['heading_font'], config('theming.default_heading_font'));
        $variables['--font-body'] = self::fontStack($settings['body_font'], config('theming.default_body_font'));

        [$small, $medium, $large] = config('theming.corners')[$settings['corner_style']] ?? config('theming.corners')[config('theming.default_corner_style')];
        $variables['--radius-sm'] = "{$small}px";
        $variables['--radius-md'] = "{$medium}px";
        $variables['--radius-lg'] = "{$large}px";

        foreach ($manifest->settings as $field) {
            $value = $settings[$field['key']] ?? $field['default'];

            $variables['--theme-'.Str::kebab(str_replace('_', '-', $field['key']))] = match (true) {
                is_bool($value) => $value ? '1' : '0',
                default => (string) $value,
            };
        }

        return $variables;
    }

    private static function fontStack(string $key, string $fallbackKey): string
    {
        $fonts = config('theming.fonts');

        return ($fonts[$key] ?? $fonts[$fallbackKey])['stack'];
    }
}
