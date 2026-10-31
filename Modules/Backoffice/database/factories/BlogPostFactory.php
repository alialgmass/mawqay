<?php

namespace Modules\Backoffice\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Backoffice\Models\BlogPost;

/**
 * @extends Factory<BlogPost>
 */
class BlogPostFactory extends Factory
{
    protected $model = BlogPost::class;

    public function definition(): array
    {
        $title = 'Published '.fake()->sentence(3, false);
        $slug = Str::slug($title, '-', false) ?: 'post-'.Str::lower(Str::random(8));

        return [
            'title' => Str::ucfirst($title),
            'title_ar' => null,
            'slug' => $slug,
            'excerpt' => fake()->sentence(12),
            'excerpt_ar' => null,
            'body' => fake()->paragraphs(3, true),
            'body_ar' => null,
            'category' => 'product',
            'author_name' => 'Mawqaay Team',
            'cover_path' => null,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    public function category(string $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }

    public function bilingual(): static
    {
        return $this->state(fn (array $attributes) => [
            'title_ar' => 'منشور ثنائي اللغة',
            'excerpt_ar' => 'نبذة ثنائية اللغة',
            'body_ar' => 'فقرة أولى.

فقرة ثانية.',
        ]);
    }
}
