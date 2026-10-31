<?php

namespace Modules\Backoffice\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Backoffice\Filament\Admin\Resources\BlogPosts\BlogPostResource;

/**
 * Contributes the Platform blog to the Admin Panel: posts shown on the
 * marketing landing and blog pages.
 */
class BlogPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'blog';
    }

    public function register(Panel $panel): void
    {
        if ($panel->getId() !== 'admin') {
            return;
        }

        $panel->resources([BlogPostResource::class]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
