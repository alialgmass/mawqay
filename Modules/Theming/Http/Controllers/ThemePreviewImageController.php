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
        $theme = Theme::query()->active()->where('slug', $slug)->firstOrFail();
        $folder = realpath($registry->path()."/{$theme->slug}");
        $file = $folder === false ? false : realpath($folder.'/'.basename($theme->manifest['preview']));

        abort_unless($file !== false && str_starts_with($file, $folder.DIRECTORY_SEPARATOR), 404);

        return response()->file($file, ['Cache-Control' => 'public, max-age=3600']);
    }
}
