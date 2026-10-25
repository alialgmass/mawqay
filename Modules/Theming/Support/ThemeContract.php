<?php

namespace Modules\Theming\Support;

/**
 * The contract between the Platform and Themes: which pages exist, which
 * version of the page data they are built against, and what a Vendor can
 * change. Only the Platform team changes this class.
 */
final class ThemeContract
{
    /** Semantic version of the page data. Minor adds data only; major breaks. */
    public const VERSION = '1.3.0';

    /** @var list<string> */
    public const REQUIRED_PAGES = ['Home', 'Category', 'Product', 'Cart', 'Checkout', 'Account', 'Orders', 'NotFound'];

    /** @var list<string> Pages a Theme may add; absent ones use the default Theme's page. */
    public const OPTIONAL_PAGES = ['Search', 'Login', 'Register', 'ForgotPassword', 'ResetPassword', 'Addresses', 'OrderDetail', 'OrderConfirmation'];

    /** @var list<string> */
    public const CORE_SETTING_KEYS = ['primary_color', 'secondary_color', 'heading_font', 'body_font', 'corner_style'];

    public static function isSemver(string $version): bool
    {
        return preg_match('/^\d+\.\d+\.\d+$/', $version) === 1;
    }

    /**
     * @return list<string>
     */
    public static function knownPages(): array
    {
        return [...self::REQUIRED_PAGES, ...self::OPTIONAL_PAGES];
    }

    /**
     * Whether a Theme built for the given contract version can be served: the
     * current major and the one before it are supported.
     */
    public static function supports(string $themeContractVersion): bool
    {
        if (! self::isSemver($themeContractVersion)) {
            return false;
        }

        $current = (int) explode('.', self::VERSION)[0];
        $theme = (int) explode('.', $themeContractVersion)[0];

        return $theme === $current || $theme === $current - 1;
    }
}
