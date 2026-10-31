<?php

namespace Modules\Tenancy\Support;

final class StoreRoutes
{
    /**
     * The pattern a `{store}` route parameter must match: anything except a
     * Reserved Slug or a dot, so Platform addresses (and files like a
     * sitemap.xml) can never be read as a Store.
     */
    public static function slugPattern(): string
    {
        $reserved = implode('|', array_map('preg_quote', config('tenancy.reserved_slugs', [])));

        return "^(?!(?:{$reserved})(?:/|$))[^./]+$";
    }
}
