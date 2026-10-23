<?php

namespace Modules\Core\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use LogicException;

/**
 * File access that is always inside the current Store's own folder,
 * `stores/{id}/`, and refuses paths that try to climb out of it.
 */
final class StoreStorage
{
    public static function disk(): Filesystem
    {
        return Storage::disk(config('filesystems.default'));
    }

    public static function path(string $relative): string
    {
        $storeId = app(StoreContext::class)->id();

        if ($storeId === null) {
            throw new LogicException('Store files need a current Store.');
        }

        $relative = ltrim(str_replace('\\', '/', $relative), '/');

        if ($relative === '' || in_array('..', explode('/', $relative), true)) {
            throw new InvalidArgumentException('The file path must stay inside the Store folder.');
        }

        return "stores/{$storeId}/{$relative}";
    }

    public static function put(string $relative, mixed $contents): bool
    {
        return (bool) self::disk()->put(self::path($relative), $contents);
    }

    public static function exists(string $relative): bool
    {
        return self::disk()->exists(self::path($relative));
    }

    public static function delete(string $relative): bool
    {
        return self::disk()->delete(self::path($relative));
    }
}
