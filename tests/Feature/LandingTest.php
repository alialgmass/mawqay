<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Backoffice\Models\BlogPost;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'A new blog post',
            'slug' => 'a-new-blog-post',
            'excerpt' => 'A short excerpt',
            'body' => "First paragraph.\n\nSecond paragraph.",
            'category' => 'product',
            'author_name' => 'Mawqaay Team',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ], $overrides);
    }

    public function test_the_home_page_renders_the_welcome_component(): void
    {
        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->has('featuredPosts')
                ->has('stats', fn (Assert $stats) => $stats
                    ->where('stores', 0)
                    ->where('products', 0))
                ->has('seo.title')
                ->has('seo.description'));
    }

    public function test_the_home_page_features_recent_published_posts(): void
    {
        BlogPost::query()->create($this->payload(['slug' => 'newer-post', 'published_at' => now()->subDay()]));
        BlogPost::query()->create($this->payload(['slug' => 'older-post', 'published_at' => now()->subDays(2)]));

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('hasFeaturedPosts', true)
                ->has('featuredPosts', 2)
                ->where('featuredPosts.0.slug', 'newer-post')
                ->where('featuredPosts.1.slug', 'older-post'));
    }

    public function test_drafts_are_never_featured_or_listed(): void
    {
        BlogPost::query()->create($this->payload([
            'slug' => 'a-hidden-draft',
            'is_published' => false,
            'published_at' => null,
        ]));

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('hasFeaturedPosts', false)
                ->has('featuredPosts', 0));

        $this->get(route('blog.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('blog/index')
                ->has('posts.data', 0));

        $this->get(route('blog.show', ['slug' => 'a-hidden-draft']))->assertNotFound();
    }

    public function test_the_blog_index_lists_published_posts_paginated(): void
    {
        BlogPost::factory()->count(10)->create();

        $this->get(route('blog.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('blog/index')
                ->has('posts.data', 9)
                ->where('posts.current_page', 1)
                ->where('posts.last_page', 2)
                ->has('seo.title'));
    }

    public function test_a_blog_post_renders_with_related_posts(): void
    {
        BlogPost::query()->create($this->payload(['slug' => 'the-shown-post', 'category' => 'guide']));
        BlogPost::query()->create($this->payload(['slug' => 'a-related-guide', 'category' => 'guide', 'published_at' => now()->subDays(2)]));
        BlogPost::query()->create($this->payload(['slug' => 'an-unrelated-story', 'category' => 'merchant_story', 'published_at' => now()->subDays(3)]));

        $this->get(route('blog.show', ['slug' => 'the-shown-post']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('blog/show')
                ->where('post.slug', 'the-shown-post')
                ->where('post.paragraphs', ['First paragraph.', 'Second paragraph.'])
                ->has('related', 1)
                ->where('related.0.slug', 'a-related-guide'));
    }

    public function test_an_unknown_blog_slug_is_not_found(): void
    {
        $this->get(route('blog.show', ['slug' => 'never-written']))->assertNotFound();
    }

    public function test_the_sitemap_lists_the_platform_pages(): void
    {
        BlogPost::query()->create($this->payload(['slug' => 'a-sitemapped-post']));
        BlogPost::query()->create($this->payload(['slug' => 'an-unlisted-draft', 'is_published' => false, 'published_at' => null]));

        $response = $this->get(route('sitemap'));

        $response->assertOk();

        $xml = (string) $response->getContent();

        $this->assertSame('application/xml', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('<loc>', $xml);
        $this->assertStringContainsString(url('/'), $xml);
        $this->assertStringContainsString(url('/blog'), $xml);
        $this->assertStringContainsString(url('/blog/a-sitemapped-post'), $xml);
        $this->assertStringNotContainsString('an-unlisted-draft', $xml);
    }

    public function test_the_home_page_exposes_canonical_and_social_seo(): void
    {
        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('seo.canonical', route('home'))
                ->where('seo.type', 'website')
                ->where('seo.image', url('/og-landing.svg')));
    }

    public function test_the_blog_pages_expose_a_canonical_url(): void
    {
        BlogPost::query()->create($this->payload(['slug' => 'canonical-post']));

        $this->get(route('blog.index'))
            ->assertInertia(fn (Assert $page) => $page->where('seo.canonical', route('blog.index')));

        $this->get(route('blog.show', ['slug' => 'canonical-post']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('seo.canonical', route('blog.show', ['slug' => 'canonical-post']))
                ->where('seo.type', 'article'));
    }

    public function test_landing_translations_have_the_same_keys_in_both_locales(): void
    {
        $flatten = function (array $values, string $prefix = '') use (&$flatten): array {
            $keys = [];

            foreach ($values as $key => $value) {
                $path = $prefix.$key;
                is_array($value) && ! array_is_list($value)
                    ? $keys = array_merge($keys, $flatten($value, $path.'.'))
                    : $keys[] = $path;
            }

            return $keys;
        };

        $en = json_decode((string) file_get_contents(resource_path('js/locales/en.json')), true)['landing'];
        $ar = json_decode((string) file_get_contents(resource_path('js/locales/ar.json')), true)['landing'];

        $this->assertEqualsCanonicalizing($flatten($en), $flatten($ar));
    }
}
