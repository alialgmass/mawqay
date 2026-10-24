<?php

namespace Modules\Core\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use LogicException;

/**
 * Cache access that is always inside the current Store: every key is prefixed
 * `store:{id}:` so one Store can never read or clear another's entries.
 */
final class StoreCache
{
    public static function key(string $key): string
    {
        $storeId = app(StoreContext::class)->id();

        if ($storeId === null) {
            throw new LogicException('Store cache keys need a current Store.');
        }

        return "store:{$storeId}:{$key}";
    }

    /**
     * The key for a specific Store, for code that clears another Store's entry
     * (a Platform-level change) rather than the current Store's own.
     */
    public static function keyFor(int $storeId, string $key): string
    {
        return "store:{$storeId}:{$key}";
    }

    public static function forgetFor(int $storeId, string $key): bool
    {
        return Cache::forget(self::keyFor($storeId, $key));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::get(self::key($key), $default);
    }

    public static function put(string $key, mixed $value, int $seconds = 3600): bool
    {
        return Cache::put(self::key($key), $value, $seconds);
    }

    public static function remember(string $key, int $seconds, Closure $callback): mixed
    {
        return Cache::remember(self::key($key), $seconds, $callback);
    }

    public static function forget(string $key): bool
    {
        return Cache::forget(self::key($key));
    }
}
