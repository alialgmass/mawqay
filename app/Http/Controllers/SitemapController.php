<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Modules\Backoffice\Models\BlogPost;

/**
 * A plain sitemap of the Platform pages: the landing, the blog index and
 * every published post.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $now = now()->toAtomString();

        $pages = [
            ['url' => route('home'), 'lastmod' => $now, 'priority' => '1.0'],
            ['url' => route('blog.index'), 'lastmod' => BlogPost::query()->published()->ordered()->first()?->published_at?->toAtomString() ?? $now, 'priority' => '0.8'],
        ];

        foreach (BlogPost::query()->published()->ordered()->get(['slug', 'published_at']) as $post) {
            $pages[] = [
                'url' => route('blog.show', ['slug' => $post->slug]),
                'lastmod' => $post->published_at?->toAtomString() ?? $now,
                'priority' => '0.6',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($pages as $page) {
            $xml .= '<url><loc>'.e($page['url']).'</loc><lastmod>'.$page['lastmod'].'</lastmod><priority>'.$page['priority'].'</priority></url>';
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
