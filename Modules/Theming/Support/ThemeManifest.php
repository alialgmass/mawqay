<?php

namespace Modules\Theming\Support;

use Modules\Theming\Exceptions\InvalidThemeManifest;

/**
 * A validated Theme manifest (theme.json). Construct it only through fromArray,
 * which refuses anything the contract does not allow.
 */
final class ThemeManifest
{
    public const FIELD_TYPES = ['color', 'select', 'range', 'toggle'];

    /**
     * @param  list<string>  $pages
     * @param  list<array<string, mixed>>  $settings
     * @param  array<string, mixed>  $defaults
     */
    private function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly string $version,
        public readonly string $contractVersion,
        public readonly string $preview,
        public readonly array $pages,
        public readonly array $settings,
        public readonly array $defaults,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidThemeManifest
     */
    public static function fromArray(array $data): self
    {
        $errors = [];

        foreach (['name', 'slug', 'version', 'contract_version', 'preview'] as $key) {
            if (! isset($data[$key]) || ! is_string($data[$key]) || trim($data[$key]) === '') {
                $errors[] = "\"{$key}\" is required and must be text.";
            }
        }

        if (is_string($data['slug'] ?? null) && preg_match('/^[a-z][a-z0-9-]*$/', $data['slug']) !== 1) {
            $errors[] = '"slug" may only contain lowercase letters, digits and dashes, starting with a letter.';
        }

        foreach (['version', 'contract_version'] as $key) {
            if (is_string($data[$key] ?? null) && ! ThemeContract::isSemver($data[$key])) {
                $errors[] = "\"{$key}\" must be a semantic version such as 1.0.0.";
            }
        }

        if (is_string($data['preview'] ?? null) && preg_match('/^[A-Za-z0-9_-]+\.(png|jpe?g|webp)$/', $data['preview']) !== 1) {
            $errors[] = '"preview" must be a png, jpg or webp file name inside the theme folder.';
        }

        $pages = $data['pages'] ?? null;

        if (! is_array($pages) || $pages === [] || ! array_is_list($pages)) {
            $errors[] = '"pages" must be a non-empty list.';
            $pages = [];
        } else {
            foreach ($pages as $page) {
                if (! in_array($page, ThemeContract::knownPages(), true)) {
                    $errors[] = 'Unknown page "'.(is_string($page) ? $page : gettype($page)).'".';
                }
            }
        }

        $settings = $data['settings'] ?? [];

        if (! is_array($settings) || ! array_is_list($settings)) {
            $errors[] = '"settings" must be a list of fields.';
            $settings = [];
        }

        $seen = [];

        foreach ($settings as $index => $field) {
            array_push($errors, ...self::fieldErrors($field, $index, $seen));
        }

        $defaults = $data['defaults'] ?? [];

        if (! is_array($defaults)) {
            $errors[] = '"defaults" must be an object.';
            $defaults = [];
        } else {
            foreach (array_diff(array_keys($defaults), ThemeContract::CORE_SETTING_KEYS) as $key) {
                $errors[] = "\"defaults\" may only set core settings, not \"{$key}\".";
            }

            array_push($errors, ...ThemeSettingsSchema::coreDefaultErrors($defaults));
        }

        if ($errors !== []) {
            throw new InvalidThemeManifest($errors);
        }

        return new self(
            name: $data['name'],
            slug: $data['slug'],
            version: $data['version'],
            contractVersion: $data['contract_version'],
            preview: $data['preview'],
            pages: array_values(array_unique($pages)),
            settings: array_values($settings),
            defaults: $defaults,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'version' => $this->version,
            'contract_version' => $this->contractVersion,
            'preview' => $this->preview,
            'pages' => $this->pages,
            'settings' => $this->settings,
            'defaults' => $this->defaults,
        ];
    }

    public function isSupported(): bool
    {
        return ThemeContract::supports($this->contractVersion);
    }

    public function hasPage(string $page): bool
    {
        return in_array($page, $this->pages, true);
    }

    /**
     * @param  array<string, true>  $seen
     * @return list<string>
     */
    private static function fieldErrors(mixed $field, int $index, array &$seen): array
    {
        $label = "settings[{$index}]";

        if (! is_array($field)) {
            return ["{$label} must be an object."];
        }

        $errors = [];
        $key = $field['key'] ?? null;
        $type = $field['type'] ?? null;

        if (! is_string($key) || preg_match('/^[a-z][a-z0-9_]*$/', $key) !== 1) {
            $errors[] = "{$label}.key must be lowercase letters, digits and underscores.";
        } elseif (in_array($key, ThemeContract::CORE_SETTING_KEYS, true)) {
            $errors[] = "{$label}.key \"{$key}\" is a core setting and cannot be redefined.";
        } elseif (isset($seen[$key])) {
            $errors[] = "{$label}.key \"{$key}\" is used twice.";
        } else {
            $seen[$key] = true;
        }

        if (! is_string($field['label'] ?? null) || trim($field['label']) === '') {
            $errors[] = "{$label}.label is required.";
        }

        if (! in_array($type, self::FIELD_TYPES, true)) {
            return [...$errors, "{$label}.type must be one of: ".implode(', ', self::FIELD_TYPES).'.'];
        }

        if (! array_key_exists('default', $field)) {
            return [...$errors, "{$label}.default is required."];
        }

        $default = $field['default'];

        $errors = [...$errors, ...match ($type) {
            'color' => is_string($default) && preg_match('/^#[0-9a-fA-F]{6}$/', $default) === 1 ? [] : ["{$label}.default must be a #RRGGBB colour."],
            'toggle' => is_bool($default) ? [] : ["{$label}.default must be true or false."],
            'select' => self::selectErrors($field, $default, $label),
            'range' => self::rangeErrors($field, $default, $label),
        }];

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return list<string>
     */
    private static function selectErrors(array $field, mixed $default, string $label): array
    {
        $options = $field['options'] ?? null;

        if (! is_array($options) || $options === [] || array_is_list($options)) {
            return ["{$label}.options must be an object of value => label."];
        }

        return array_key_exists((string) $default, $options) ? [] : ["{$label}.default must be one of the options."];
    }

    /**
     * @param  array<string, mixed>  $field
     * @return list<string>
     */
    private static function rangeErrors(array $field, mixed $default, string $label): array
    {
        foreach (['min', 'max'] as $bound) {
            if (! is_int($field[$bound] ?? null) && ! is_float($field[$bound] ?? null)) {
                return ["{$label}.{$bound} must be a number."];
            }
        }

        if ($field['min'] >= $field['max']) {
            return ["{$label}.min must be below max."];
        }

        if ((! is_int($default) && ! is_float($default)) || $default < $field['min'] || $default > $field['max']) {
            return ["{$label}.default must be a number between min and max."];
        }

        return [];
    }
}
