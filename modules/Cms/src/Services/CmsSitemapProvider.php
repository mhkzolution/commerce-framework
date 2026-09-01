<?php

declare(strict_types=1);

namespace Commerce\Cms\Services;

use Commerce\Cms\Models\Category;
use Commerce\Cms\Models\Page;
use Commerce\Cms\Models\Post;
use Commerce\Cms\Models\Tag;
use Commerce\Contracts\Seo\SitemapProviderInterface;
use Illuminate\Support\Facades\Schema;

final class CmsSitemapProvider implements SitemapProviderInterface
{
    /**
     * @return list<array{loc: string, lastmod?: ?string, priority?: string}>
     */
    public function urls(): array
    {
        $urls = [];

        if (Schema::hasTable('cms_posts')) {
            $urls[] = [
                'loc' => route('storefront.cms.posts.index'),
                'priority' => '0.8',
            ];

            Post::query()
                ->where('status', 'published')
                ->whereNotNull('slug')
                ->get(['slug', 'updated_at', 'published_at'])
                ->each(function (Post $post) use (&$urls): void {
                    $urls[] = [
                        'loc' => route('storefront.cms.posts.show', $post->slug),
                        'lastmod' => ($post->updated_at ?? $post->published_at)?->toAtomString(),
                        'priority' => '0.6',
                    ];
                });
        }

        if (Schema::hasTable('cms_categories')) {
            Category::query()
                ->where('is_active', true)
                ->whereNotNull('slug')
                ->get(['slug', 'updated_at'])
                ->each(function (Category $category) use (&$urls): void {
                    $urls[] = [
                        'loc' => route('storefront.cms.categories.show', $category->slug),
                        'lastmod' => $category->updated_at?->toAtomString(),
                        'priority' => '0.5',
                    ];
                });
        }

        if (Schema::hasTable('cms_tags')) {
            Tag::query()
                ->whereNotNull('slug')
                ->get(['slug', 'updated_at'])
                ->each(function (Tag $tag) use (&$urls): void {
                    $urls[] = [
                        'loc' => route('storefront.cms.tags.show', $tag->slug),
                        'lastmod' => $tag->updated_at?->toAtomString(),
                        'priority' => '0.5',
                    ];
                });
        }

        if (Schema::hasTable('cms_pages')) {
            Page::query()
                ->where('status', 'published')
                ->whereNotNull('slug')
                ->get(['slug', 'updated_at'])
                ->each(function (Page $page) use (&$urls): void {
                    $urls[] = [
                        'loc' => route('storefront.cms.pages.show', $page->slug),
                        'lastmod' => $page->updated_at?->toAtomString(),
                        'priority' => '0.6',
                    ];
                });
        }

        return $urls;
    }
}
