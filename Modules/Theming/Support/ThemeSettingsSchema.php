<?php

namespace Modules\Theming\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * What a Vendor may change for one Theme: the five core settings every Theme
 * has, plus the fields the Theme's manifest declares. One definition drives
 * the Appearance form, validation and defaults.
 */
final class ThemeSettingsSchema
{
    public function __construct(private readonly ThemeManifest $manifest) {}

    /**
     * Problems with a manifest's overrides of the core defaults.
     *
     * @param  array<string, mixed>  $defaults
     * @return list<string>
     */
    public static function coreDefaultErrors(array $defaults): array
    {
        $errors = [];

        foreach (self::coreFields([]) as $field) {
            if (! array_key_exists($field['key'], $defaults)) {
                continue;
            }

            $validator = Validator::make([$field['key'] => $defaults[$field['key']]], [$field['key'] => self::rulesFor($field)]);

            if ($validator->fails()) {
                $errors[] = "\"defaults.{$field['key']}\" is not a valid value.";
            }
        }

        return $errors;
    }

    /**
     * Every field in display order, core first.
     *
     * @return list<array<string, mixed>>
     */
    public function fields(): array
    {
        return [
            ...self::coreFields($this->manifest->defaults),
            ...array_map(fn (array $field): array => $field + ['core' => false], $this->manifest->settings),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return collect($this->fields())->mapWithKeys(fn (array $field): array => [$field['key'] => $field['default']])->all();
    }

    /**
     * Check Vendor input and return only what is valid and set. A null clears a
     * value back to the default. Anything outside the schema is refused.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validate(array $input): array
    {
        $fields = collect($this->fields())->keyBy('key');

        $unknown = array_diff(array_keys($input), $fields->keys()->all());

        if ($unknown !== []) {
            throw ValidationException::withMessages(
                collect($unknown)->mapWithKeys(fn (string $key): array => [$key => __('theming::appearance.errors.unknown_setting')])->all(),
            );
        }

        $validated = Validator::make($input, $fields->map(fn (array $field): array => self::rulesFor($field))->all())->validate();

        $clean = [];

        foreach ($validated as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $clean[$key] = match ($fields[$key]['type']) {
                'toggle' => (bool) $value,
                'range' => $value + 0,
                default => $value,
            };
        }

        return $clean;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return list<array<string, mixed>>
     */
    private static function coreFields(array $overrides): array
    {
        $tokens = config('storefront.tokens');
        $fonts = collect(config('theming.fonts'))->map(fn (array $font): string => $font['label'])->all();

        return [
            ['key' => 'primary_color', 'type' => 'color', 'label' => 'primary_color', 'default' => $overrides['primary_color'] ?? $tokens['--color-primary'], 'core' => true],
            ['key' => 'secondary_color', 'type' => 'color', 'label' => 'secondary_color', 'default' => $overrides['secondary_color'] ?? $tokens['--color-secondary'], 'core' => true],
            ['key' => 'heading_font', 'type' => 'select', 'label' => 'heading_font', 'options' => $fonts, 'default' => $overrides['heading_font'] ?? config('theming.default_heading_font'), 'core' => true],
            ['key' => 'body_font', 'type' => 'select', 'label' => 'body_font', 'options' => $fonts, 'default' => $overrides['body_font'] ?? config('theming.default_body_font'), 'core' => true],
            ['key' => 'corner_style', 'type' => 'select', 'label' => 'corner_style', 'options' => ['sharp' => 'sharp', 'soft' => 'soft'], 'default' => $overrides['corner_style'] ?? config('theming.default_corner_style'), 'core' => true],
        ];
    }

    /**
     * @param  array<string, mixed>  $field
     * @return list<mixed>
     */
    private static function rulesFor(array $field): array
    {
        return match ($field['type']) {
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'select' => ['nullable', 'string', Rule::in(array_map('strval', array_keys($field['options'])))],
            'range' => ['nullable', 'numeric', 'between:'.$field['min'].','.$field['max']],
            'toggle' => ['nullable', 'boolean'],
        };
    }
}
