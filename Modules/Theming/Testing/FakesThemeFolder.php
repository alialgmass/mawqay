<?php

namespace Modules\Theming\Testing;

use Illuminate\Support\Facades\File;
use Modules\Theming\Support\ThemeContract;

/**
 * Points the Theme registry at a throwaway folder that already holds a valid
 * default Theme, so tests can add Themes without touching the real ones.
 */
trait FakesThemeFolder
{
    protected string $themeFolder = '';

    protected function useThemeFolder(): void
    {
        $this->themeFolder = sys_get_temp_dir().'/themes-'.uniqid();
        File::ensureDirectoryExists($this->themeFolder);
        config(['theming.path' => $this->themeFolder]);

        $this->makeTheme('default', ['name' => 'Default'], ThemeContract::REQUIRED_PAGES);
    }

    protected function tearDownThemeFolder(): void
    {
        File::deleteDirectory($this->themeFolder);
    }

    /**
     * @param  array<string, mixed>  $overrides  manifest keys to change
     * @param  list<string>  $pages
     */
    protected function makeTheme(string $slug, array $overrides = [], array $pages = ThemeContract::REQUIRED_PAGES): string
    {
        $folder = "{$this->themeFolder}/{$slug}";
        File::ensureDirectoryExists("{$folder}/pages");
        File::copy(resource_path('themes/default/preview.png'), "{$folder}/preview.png");

        foreach ($pages as $page) {
            File::put("{$folder}/pages/{$page}.vue", '<template><div /></template>');
        }

        File::put("{$folder}/theme.json", json_encode([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'version' => '1.0.0',
            'contract_version' => ThemeContract::VERSION,
            'preview' => 'preview.png',
            'pages' => $pages,
            ...$overrides,
        ], JSON_UNESCAPED_UNICODE));

        return $folder;
    }
}
