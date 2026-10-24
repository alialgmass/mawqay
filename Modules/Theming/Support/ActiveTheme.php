<?php

namespace Modules\Theming\Support;

/**
 * The Theme a request renders with, and the settings it renders with.
 */
final class ActiveTheme
{
    /**
     * @param  array<string, mixed>  $settings  resolved settings, every schema key present
     */
    public function __construct(
        public readonly ThemeManifest $manifest,
        public readonly array $settings,
    ) {}

    public function slug(): string
    {
        return $this->manifest->slug;
    }

    /**
     * The Inertia component for a page: the Theme's own when it supports the
     * contract and provides the page, otherwise the default Theme's.
     */
    public function componentFor(string $page): string
    {
        $theme = $this->manifest->isSupported() && $this->manifest->hasPage($page)
            ? $this->manifest->slug
            : config('theming.default');

        return "storefront/{$theme}/{$page}";
    }

    /**
     * @return array<string, string>
     */
    public function styleVariables(): array
    {
        return StyleVariables::from($this->manifest, $this->settings);
    }
}
