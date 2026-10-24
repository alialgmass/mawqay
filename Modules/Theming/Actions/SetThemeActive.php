<?php

namespace Modules\Theming\Actions;

use Illuminate\Validation\ValidationException;
use Modules\Theming\Models\Theme;

/**
 * Turns a Theme on or off for Vendors. The default Theme is every Store's
 * fallback, so it can never be turned off. Stores already using a Theme that
 * is turned off render with the default until it is turned back on.
 */
class SetThemeActive
{
    public function __invoke(Theme $theme, bool $active): Theme
    {
        if (! $active && $theme->isDefault()) {
            throw ValidationException::withMessages(['theme' => __('theming::themes.errors.default_always_active')]);
        }

        $theme->update(['is_active' => $active]);

        return $theme;
    }
}
