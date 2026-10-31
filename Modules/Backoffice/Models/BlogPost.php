<?php

namespace Modules\Backoffice\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Modules\Backoffice\Database\Factories\BlogPostFactory;

/**
 * A Platform blog post: bilingual, manually translated, either published on
 * the marketing pages or a draft only the Admin can see.
 */
#[Fillable([
    'title', 'title_ar', 'slug', 'excerpt', 'excerpt_ar', 'body', 'body_ar',
    'category', 'author_name', 'cover_path', 'is_published', 'published_at',
])]
class BlogPost extends Model
{
    /** @use HasFactory<BlogPostFactory> */
    use HasFactory;

    protected static function newFactory(): BlogPostFactory
    {
        return BlogPostFactory::new();
    }

    /**
     * @param  Builder<BlogPost>  $query
     * @return Builder<BlogPost>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * @return Builder<BlogPost>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('published_at')->orderByDesc('id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toCard(): array
    {
        return [
            'title' => $this->localizedTitle(),
            'slug' => $this->slug,
            'excerpt' => $this->localizedExcerpt(),
            'category' => $this->category,
            'date' => $this->published_at?->toISOString(),
            'coverUrl' => $this->coverUrl(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDetail(): array
    {
        return [
            ...$this->toCard(),
            'author' => $this->author_name,
            'paragraphs' => $this->localizedParagraphs(),
        ];
    }

    public function localizedTitle(): string
    {
        return app()->getLocale() === 'ar' ? ($this->title_ar ?? $this->title) : $this->title;
    }

    public function localizedExcerpt(): ?string
    {
        return app()->getLocale() === 'ar' ? ($this->excerpt_ar ?? $this->excerpt) : $this->excerpt;
    }

    public function localizedBody(): string
    {
        return app()->getLocale() === 'ar' ? ($this->body_ar ?? $this->body) : $this->body;
    }

    /**
     * The body split into paragraphs: the Marketing pages never render raw HTML.
     *
     * @return list<string>
     */
    private function localizedParagraphs(): array
    {
        return array_values(array_filter(array_map(
            fn (string $paragraph): string => trim($paragraph),
            preg_split('/\R{2,}|\n/', $this->localizedBody()) ?: [],
        )));
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
