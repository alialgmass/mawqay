<?php

namespace Modules\Theming\Http\Controllers;

use Modules\Theming\Models\Theme;
use Modules\Theming\Support\ThemeRegistry;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves a registered Theme's preview image from its folder.
 */
class ThemePreviewImageController
{
    public function __invoke(ThemeRegistry $registry, string $slug): BinaryFileResponse
    {
        $theme = Theme::query()->where('slug', $slug)->firstOrFail();
        $file = $registry->path()."/{$theme->slug}/".$theme->manifest['preview'];

        abort_unless(is_file($file), 404);

        return response()->file($file, ['Cache-Control' => 'public, max-age=3600']);
    }
}
