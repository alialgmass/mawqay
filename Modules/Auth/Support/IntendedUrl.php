<?php

namespace Modules\Auth\Support;

use Modules\Tenancy\Models\Store;

/**
 * Where to send a Customer after login. Only a page inside the same Store is
 * accepted, so a crafted link can never send anyone to another site or Store.
 */
final class IntendedUrl
{
    private const SESSION_KEY = 'customer.intended';

    /**
     * The path (and query) if it is a page of the Store, otherwise null.
     */
    public static function sanitize(?string $target, Store $store): ?string
    {
        if ($target === null || $target === '' || preg_match('/[\x00-\x1F\\\\]/', $target) === 1) {
            return null;
        }

        $parts = parse_url($target);

        if ($parts === false || isset($parts['scheme']) || isset($parts['host']) || isset($parts['user'])) {
            return null;
        }

        $path = $parts['path'] ?? '';
        $prefix = '/'.$store->slug;

        if ($path !== $prefix && ! str_starts_with($path, $prefix.'/')) {
            return null;
        }

        if (str_contains($path, '//') || in_array('..', explode('/', $path), true)) {
            return null;
        }

        return $path.(isset($parts['query']) ? '?'.$parts['query'] : '');
    }

    public static function remember(string $target, Store $store): void
    {
        $clean = self::sanitize($target, $store);

        if ($clean !== null) {
            session([self::SESSION_KEY => $clean]);
        }
    }

    /**
     * The remembered page, then forgotten.
     */
    public static function pull(Store $store): ?string
    {
        return self::sanitize(session()->pull(self::SESSION_KEY), $store);
    }
}
