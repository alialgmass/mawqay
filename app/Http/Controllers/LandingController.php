<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Backoffice\Models\BlogPost;
use Modules\Catalog\Models\Product;
use Modules\Tenancy\Enums\StoreStatus;
use Modules\Tenancy\Models\Store;
use Symfony\Component\HttpFoundation\Response;

class LandingController extends Controller
{
    private const FEATURED_COUNT = 3;

    private const POSTS_PER_PAGE = 9;

    private const RELATED_COUNT = 3;

    public function index(Request $request): Response
    {
        return Inertia::render('Welcome', [
            'featuredPosts' => BlogPost::query()->published()->ordered()->limit(self::FEATURED_COUNT)->get()
                ->map(fn (BlogPost $post): array => $post->toCard())->all(),
            'hasFeaturedPosts' => BlogPost::query()->published()->exists(),
            'stats' => [
                'stores' => Store::query()->where('status', StoreStatus::Active)->count(),
                'products' => Product::query()->published()->count(),
            ],
            'seo' => [
                'title' => __('app.title').' — '.__('app.landing.tagline'),
                'description' => __('app.landing.meta_description'),
                ...$this->socialSeo(route('home'), 'website'),
            ],
        ])->toResponse($request);
    }

    public function blog(Request $request): Response
    {
        return Inertia::render('blog/index', [
            'posts' => BlogPost::query()->published()->ordered()
                ->paginate(self::POSTS_PER_PAGE)
                ->onEachSide(1)
                ->through(fn (BlogPost $post): array => $post->toCard()),
            'seo' => [
                'title' => __('app.landing.blog_meta_title'),
                'description' => __('app.landing.blog_meta_description'),
                ...$this->socialSeo(route('blog.index'), 'website'),
            ],
        ])->toResponse($request);
    }

    public function blogShow(Request $request, string $slug): Response
    {
        $post = BlogPost::query()->published()->where('slug', $slug)->first();

        if ($post === null) {
            abort(404);
        }

        $related = BlogPost::query()->published()->ordered()
            ->when($post->category !== null, fn ($query) => $query->where('category', $post->category))
            ->whereKeyNot($post->id)
            ->limit(self::RELATED_COUNT)
            ->get();

        return Inertia::render('blog/show', [
            'post' => $post->toDetail(),
            'related' => $related->map(fn (BlogPost $relatedPost): array => $relatedPost->toCard())->all(),
            'seo' => [
                'title' => $post->localizedTitle().' | '.__('app.title'),
                'description' => $post->localizedExcerpt() ?? __('app.landing.blog_meta_description'),
                ...$this->socialSeo(route('blog.show', ['slug' => $post->slug]), 'article'),
            ],
        ])->toResponse($request);
    }

    /**
     * @return array{canonical: string, type: string, image: string}
     */
    private function socialSeo(string $canonical, string $type): array
    {
        return [
            'canonical' => $canonical,
            'type' => $type,
            'image' => url('/og-landing.svg'),
        ];
    }
}
