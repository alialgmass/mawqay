<?php

namespace Modules\Theming\Support;

use Illuminate\Support\Collection;
use Modules\Theming\Exceptions\InvalidThemeManifest;
use Modules\Theming\Models\Theme;

/**
 * Finds Theme folders and records them in the themes table. Registering is
 * idempotent: it creates a Theme or refreshes its name, version and manifest,
 * and never changes whether the Theme is active.
 */
class ThemeRegistry
{
    public function path(): string
    {
        return rtrim(config('theming.path'), '/\\');
    }

    /**
     * @throws InvalidThemeManifest
     */
    public function register(string $slug): Theme
    {
        $manifest = $this->readManifest($slug);

        $theme = Theme::query()->firstOrNew(['slug' => $manifest->slug]);
        $theme->fill([
            'name' => $manifest->name,
            'version' => $manifest->version,
            'manifest' => $manifest->toArray(),
        ]);

        if (! $theme->exists) {
            $theme->is_active = true;
        }

        $theme->save();

        return $theme;
    }

    /**
     * Register every Theme folder. Invalid ones are skipped and reported.
     *
     * @return array{registered: Collection<int, Theme>, failed: array<string, list<string>>}
     */
    public function scan(): array
    {
        $registered = collect();
        $failed = [];

        foreach (glob($this->path().'/*', GLOB_ONLYDIR) ?: [] as $folder) {
            $slug = basename($folder);

            try {
                $registered->push($this->register($slug));
            } catch (InvalidThemeManifest $exception) {
                $failed[$slug] = $exception->errors;
            }
        }

        return ['registered' => $registered, 'failed' => $failed];
    }

    /**
     * @throws InvalidThemeManifest
     */
    public function readManifest(string $slug): ThemeManifest
    {
        if (preg_match('/^[a-z][a-z0-9-]*$/', $slug) !== 1) {
            throw new InvalidThemeManifest(["\"{$slug}\" is not a valid theme slug."]);
        }

        $folder = $this->path()."/{$slug}";
        $file = "{$folder}/theme.json";

        if (! is_file($file)) {
            throw new InvalidThemeManifest(["No theme.json found in {$folder}."]);
        }

        $data = json_decode((string) file_get_contents($file), true);

        if (! is_array($data)) {
            throw new InvalidThemeManifest(['theme.json is not valid JSON.']);
        }

        $manifest = ThemeManifest::fromArray($data);
        $errors = [];

        if ($manifest->slug !== $slug) {
            $errors[] = "The manifest slug \"{$manifest->slug}\" must match its folder name \"{$slug}\".";
        }

        if (! is_file("{$folder}/{$manifest->preview}")) {
            $errors[] = "The preview image \"{$manifest->preview}\" does not exist.";
        }

        foreach ($manifest->pages as $page) {
            if (! is_file("{$folder}/pages/{$page}.vue")) {
                $errors[] = "The page \"{$page}\" is declared but pages/{$page}.vue does not exist.";
            }
        }

        if ($slug === config('theming.default')) {
            foreach (array_diff(ThemeContract::REQUIRED_PAGES, $manifest->pages) as $page) {
                $errors[] = "The default theme must provide the \"{$page}\" page.";
            }

            if (! $manifest->isSupported()) {
                $errors[] = 'The default theme must target a supported contract version.';
            }
        }

        if ($errors !== []) {
            throw new InvalidThemeManifest($errors);
        }

        return $manifest;
    }
}
