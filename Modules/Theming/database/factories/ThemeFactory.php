<?php

namespace Modules\Theming\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Theming\Models\Theme;
use Modules\Theming\Support\ThemeContract;

/**
 * @extends Factory<Theme>
 */
class ThemeFactory extends Factory
{
    protected $model = Theme::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = 'theme-'.fake()->unique()->numerify('####');

        return [
            'slug' => $slug,
            'name' => ucfirst($slug),
            'version' => '1.0.0',
            'is_active' => true,
            'manifest' => [
                'name' => ucfirst($slug),
                'slug' => $slug,
                'version' => '1.0.0',
                'contract_version' => ThemeContract::VERSION,
                'preview' => 'preview.png',
                'pages' => ThemeContract::REQUIRED_PAGES,
                'settings' => [],
                'defaults' => [],
            ],
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
