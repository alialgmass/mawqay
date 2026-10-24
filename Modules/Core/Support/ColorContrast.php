<?php

namespace Modules\Core\Support;

use InvalidArgumentException;

/**
 * WCAG contrast maths for Store colours.
 */
final class ColorContrast
{
    public const MINIMUM_UI_RATIO = 3.0;

    public static function isHex(string $color): bool
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $color) === 1;
    }

    /**
     * The WCAG contrast ratio between two colours, from 1 to 21.
     */
    public static function ratio(string $first, string $second): float
    {
        $lighter = max(self::luminance($first), self::luminance($second));
        $darker = min(self::luminance($first), self::luminance($second));

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Black or white, whichever reads better on the given background.
     */
    public static function readableTextOn(string $background): string
    {
        return self::ratio($background, '#000000') >= self::ratio($background, '#FFFFFF')
            ? '#000000'
            : '#FFFFFF';
    }

    /**
     * Whether the colour fades into a white page (below the WCAG 3:1 minimum for
     * interface colours). Black or white text on the colour is always readable,
     * so the page background is the contrast that can actually fail.
     */
    public static function isHardToSeeOnPage(string $color): bool
    {
        return self::ratio($color, '#FFFFFF') < self::MINIMUM_UI_RATIO;
    }

    private static function luminance(string $color): float
    {
        if (! self::isHex($color)) {
            throw new InvalidArgumentException("[{$color}] is not a #RRGGBB colour.");
        }

        $channels = array_map(function (string $hex): float {
            $value = hexdec($hex) / 255;

            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, str_split(ltrim($color, '#'), 2));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }
}
