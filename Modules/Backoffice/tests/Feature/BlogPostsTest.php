<?php

namespace Modules\Backoffice\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Backoffice\Filament\Admin\Resources\BlogPosts\Pages\CreateBlogPost;
use Modules\Backoffice\Filament\Admin\Resources\BlogPosts\Pages\ListBlogPosts;
use Modules\Backoffice\Models\BlogPost;
use Tests\TestCase;

class BlogPostsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();

        $this->actingAs($this->admin);
    }

    private function fields(array $overrides = []): array
    {
        $data = [
            'title' => 'A new blog post',
            'title_ar' => 'منشور جديد',
            'slug' => 'a-new-blog-post',
            'excerpt' => 'A short excerpt',
            'excerpt_ar' => 'نبذة قصيرة',
            'body' => "Paragraph one.\n\nParagraph two.",
            'body_ar' => "فقرة أولى.\n\nفقرة ثانية.",
            'category' => 'product',
            'author_name' => 'Mawqaay Team',
            'is_published' => true,
            'published_at' => now()->subDay()->format('Y-m-d H:i:s'),
        ];

        return [...$data, ...$overrides];
    }

    public function test_the_blog_list_shows_posts(): void
    {
        $posts = BlogPost::factory()->count(3)->create();

        Livewire::test(ListBlogPosts::class)
            ->assertCanSeeTableRecords($posts);
    }

    public function test_an_admin_creates_a_post_and_it_is_published(): void
    {
        Livewire::test(CreateBlogPost::class)
            ->fillForm($this->fields())
            ->call('create')
            ->assertHasNoFormErrors();

        $post = BlogPost::query()->where('slug', 'a-new-blog-post')->first();

        $this->assertNotNull($post);
        $this->assertTrue($post->is_published);
        $this->assertNotNull($post->published_at);
    }

    public function test_a_missing_slug_is_generated(): void
    {
        Livewire::test(CreateBlogPost::class)
            ->fillForm($this->fields(['slug' => null]))
            ->call('create')
            ->assertHasNoFormErrors();

        $post = BlogPost::query()->where('title', 'A new blog post')->first();

        $this->assertNotNull($post);
        $this->assertNotSame('', $post->slug);
        $this->assertTrue(preg_match('/^[a-z0-9-]+$/', $post->slug) === 1);
    }

    public function test_published_scopes_and_casts_keep_drafts_hidden(): void
    {
        $draft = BlogPost::query()->create($this->fields([
            'slug' => 'another-draft',
            'is_published' => false,
            'published_at' => null,
        ]));

        $this->assertFalse(BlogPost::query()->published()->whereKey($draft->id)->exists());
        $this->assertNotNull($draft->fresh()->id);
    }

    public function test_arabic_uses_the_localized_fields(): void
    {
        $post = BlogPost::query()->create($this->fields());

        $this->assertSame('A new blog post', $post->localizedTitle());

        $this->app->setLocale('ar');
        $this->assertSame('منشور جديد', $post->localizedTitle());
        $this->app->setLocale('en');
    }
}
