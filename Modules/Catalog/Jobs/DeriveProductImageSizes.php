<?php

namespace Modules\Catalog\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Models\ProductMedia;
use Modules\Core\Jobs\CarriesStore;

/**
 * Creates the thumb, medium and large WebP versions of an uploaded image next
 * to the original, which is kept untouched.
 */
class DeriveProductImageSizes implements ShouldQueue
{
    use CarriesStore;
    use Dispatchable;
    use Queueable;

    /** Longest edge in pixels of each derived size. */
    private const WIDTHS = ['thumb' => 200, 'medium' => 600, 'large' => 1200];

    public function __construct(public int $mediaId)
    {
        $this->captureStore();
    }

    public function handle(): void
    {
        $media = ProductMedia::query()->find($this->mediaId);
        $disk = Storage::disk('public');

        if ($media === null || ! $disk->exists($media->path)) {
            return;
        }

        $source = @imagecreatefromstring((string) $disk->get($media->path));

        if ($source === false) {
            return;
        }

        foreach (self::WIDTHS as $size => $longestEdge) {
            $disk->put($media->derivedPath($size), $this->encodeWebp($source, $longestEdge), 'public');
        }

        $media->forceFill(['processed_at' => now()])->save();
    }

    /**
     * @param  \GdImage  $source
     */
    private function encodeWebp($source, int $longestEdge): string
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, $longestEdge / max($width, $height));

        $resized = imagescale($source, max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));

        ob_start();
        imagewebp($resized, null, 82);

        return (string) ob_get_clean();
    }
}
