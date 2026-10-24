<?php

namespace Modules\Theming\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Modules\Core\Support\ColorContrast;
use Modules\Core\Support\StoreCache;
use Modules\Tenancy\Models\Store;
use Modules\Theming\Models\Theme;

/**
 * Decides which Theme and settings a Store renders with. The published choice
 * is cached per Store; a preview by the Store's own Vendor Owner overrides it
 * for that request only and writes nothing.
 */
class ThemeManager
{
    private const VERSION_KEY = 'themes:version';

    public function __construct(private readonly ThemeRegistry $registry) {}

    public function activeFor(Store $store, ?Request $request = null): ActiveTheme
    {
        $previewTheme = $this->previewTheme($store, $request);

        $theme = $previewTheme ?? $this->publishedTheme($store);
        $manifest = ThemeManifest::fromArray($theme['manifest']);

        $stored = $store->theme_settings[$manifest->slug] ?? [];

        if ($previewTheme !== null) {
            $stored = $this->previewSettings($manifest, $request) ?? $stored;
        }

        return new ActiveTheme($manifest, $this->resolve($manifest, $stored, $store));
    }

    /**
     * Forget every cached Theme choice; used when a Theme is added, changed,
     * activated or deactivated.
     */
    public static function flush(): void
    {
        Cache::add(self::VERSION_KEY, 0);
        Cache::increment(self::VERSION_KEY);
    }

    /**
     * Forget one Store's cached choice; used when the Store picks another Theme.
     */
    public static function flushStore(int $storeId): void
    {
        StoreCache::forgetFor($storeId, 'theme');
    }

    /**
     * The settings to show: stored values over the Store's identity colours
     * over the Theme's defaults.
     *
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    public function resolve(ThemeManifest $manifest, array $stored, ?Store $store = null): array
    {
        $schema = new ThemeSettingsSchema($manifest);
        $settings = $schema->defaults();

        foreach (['primary_color', 'secondary_color'] as $key) {
            $identity = $store?->{$key};

            if (is_string($identity) && ColorContrast::isHex($identity)) {
                $settings[$key] = $identity;
            }
        }

        return array_replace($settings, array_intersect_key($stored, $settings));
    }

    /**
     * @return array{slug: string, manifest: array<string, mixed>, version: int}
     */
    private function publishedTheme(Store $store): array
    {
        $version = (int) Cache::get(self::VERSION_KEY, 0);
        $key = StoreCache::keyFor($store->id, 'theme');
        $cached = Cache::get($key);

        if (is_array($cached) && ($cached['version'] ?? null) === $version && ($cached['theme_id'] ?? null) === $store->theme_id) {
            return $cached;
        }

        $chosen = $store->theme_id ? Theme::query()->active()->whereKey($store->theme_id)->first() : null;

        $theme = $this->chosenOrDefault($chosen);

        $payload = ['slug' => $theme->slug, 'manifest' => $theme->manifest, 'version' => $version, 'theme_id' => $store->theme_id];

        Cache::put($key, $payload, 3600);

        return $payload;
    }

    /**
     * The chosen Theme, unless it is gone from disk, in which case the default.
     */
    private function chosenOrDefault(?Theme $chosen): Theme
    {
        if ($chosen !== null && is_file($this->registry->path()."/{$chosen->slug}/theme.json")) {
            return $chosen;
        }

        return Theme::query()->where('slug', config('theming.default'))->first()
            ?? $this->registry->register(config('theming.default'));
    }

    /**
     * @return array{slug: string, manifest: array<string, mixed>}|null
     */
    private function previewTheme(Store $store, ?Request $request): ?array
    {
        $slug = $request?->query('preview_theme');

        if (! is_string($slug) || ! $this->canPreview($store)) {
            return null;
        }

        $theme = Theme::query()->active()->where('slug', $slug)->first();

        return $theme ? ['slug' => $theme->slug, 'manifest' => $theme->manifest] : null;
    }

    private function canPreview(Store $store): bool
    {
        $user = Auth::guard('web')->user();

        return $user instanceof User && $user->isVendorOwner() && $user->store_id === $store->id;
    }

    /**
     * Unsaved settings sent by the Appearance page; invalid ones are ignored.
     *
     * @return array<string, mixed>|null
     */
    private function previewSettings(ThemeManifest $manifest, ?Request $request): ?array
    {
        $input = $request?->query('preview_settings');

        if (! is_array($input)) {
            return null;
        }

        try {
            return (new ThemeSettingsSchema($manifest))->validate($input);
        } catch (ValidationException) {
            return null;
        }
    }
}
