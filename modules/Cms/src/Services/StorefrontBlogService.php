<?php

declare(strict_types=1);

namespace Commerce\Cms\Services;

use Commerce\Cms\DTO\StorefrontBlogFilters;
use Commerce\Cms\Models\Category;
use Commerce\Cms\Models\Post;
use Commerce\Cms\Models\Tag;
use Commerce\Cms\Support\CmsSeoSync;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Iam\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class StorefrontBlogService
{
    public function __construct(
        private readonly BlogContentFormatter $contentFormatter,
        private readonly CmsSeoSync $cmsSeo,
    ) {}

    private function mediaQuery(): ?MediaQueryServiceInterface
    {
        return app()->bound(MediaQueryServiceInterface::class)
            ? app(MediaQueryServiceInterface::class)
            : null;
    }

    /**
     * @return LengthAwarePaginator<int, Post>
     */
    public function paginate(StorefrontBlogFilters $filters): LengthAwarePaginator
    {
        $query = $this->publishedQuery()->with(['category', 'tags', 'author']);

        if ($filters->search) {
            $search = $filters->search;
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($filters->category) {
            $query->whereHas('category', static fn (Builder $inner) => $inner->where('slug', $filters->category));
        }

        if ($filters->tag) {
            $query->whereHas('tags', static fn (Builder $inner) => $inner->where('slug', $filters->tag));
        }

        if ($filters->authorUuid) {
            $query->where('author_uuid', $filters->authorUuid);
        }

        return $query
            ->latest('published_at')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    public function featuredPost(?Post $excludeFromGrid = null): ?Post
    {
        $featured = $this->publishedQuery()
            ->with(['category', 'tags', 'author'])
            ->where('is_featured', true)
            ->latest('published_at')
            ->first();

        if ($featured !== null) {
            return $featured;
        }

        return $this->publishedQuery()
            ->with(['category', 'tags', 'author'])
            ->when($excludeFromGrid, static fn (Builder $query) => $query->where('id', '!=', $excludeFromGrid->id))
            ->latest('published_at')
            ->first();
    }

    /**
     * @return Collection<int, Category>
     */
    public function categories(): Collection
    {
        return Category::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return list<array{name: string, slug: string, count: int}>
     */
    public function popularTags(int $limit = 12): array
    {
        return Tag::query()
            ->withCount(['posts as published_posts_count' => function (Builder $query): void {
                $query->where('status', 'published');
            }])
            ->get()
            ->filter(static fn (Tag $tag): bool => (int) $tag->published_posts_count > 0)
            ->sortByDesc('published_posts_count')
            ->take($limit)
            ->values()
            ->map(static fn (Tag $tag): array => [
                'name' => $tag->name,
                'slug' => (string) $tag->slug,
                'count' => (int) $tag->published_posts_count,
            ])
            ->all();
    }

    /**
     * @return Collection<int, Post>
     */
    public function recentPosts(int $limit = 5, ?Post $exclude = null): Collection
    {
        return $this->publishedQuery()
            ->with(['category', 'author'])
            ->when($exclude, static fn (Builder $query) => $query->where('id', '!=', $exclude->id))
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Post>
     */
    public function relatedPosts(Post $post, int $limit = 3): Collection
    {
        $uuids = data_get($post->meta, 'related_post_uuids', []);

        if (is_array($uuids) && $uuids !== []) {
            $posts = $this->publishedQuery()
                ->with(['category', 'author'])
                ->whereIn('uuid', $uuids)
                ->where('id', '!=', $post->id)
                ->get()
                ->keyBy('uuid');

            return collect($uuids)
                ->map(static fn (string $uuid) => $posts->get($uuid))
                ->filter()
                ->take($limit)
                ->values();
        }

        if ($post->category_id === null) {
            return collect();
        }

        return $this->publishedQuery()
            ->with(['category', 'author'])
            ->where('id', '!=', $post->id)
            ->where('category_id', $post->category_id)
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    public function featuredImageUrl(Post $post, ?string $variant = 'large'): ?string
    {
        $uuid = $post->featured_image_media_uuid
            ?: data_get($post->meta, 'featured_image_media_uuid');

        if (! is_string($uuid) || $uuid === '') {
            return null;
        }

        $mediaQuery = $this->mediaQuery();
        if ($mediaQuery === null) {
            return null;
        }

        return $mediaQuery->getUrl($uuid, $variant)
            ?? $mediaQuery->getUrl($uuid, 'medium')
            ?? $mediaQuery->getUrl($uuid);
    }

    public function readingTime(Post $post): int
    {
        $fromMeta = data_get($post->meta, 'reading_time_minutes');

        if (is_numeric($fromMeta) && (int) $fromMeta > 0) {
            return (int) $fromMeta;
        }

        return $this->contentFormatter->readingTimeMinutes($post->content);
    }

    public function authorName(Post $post): ?string
    {
        $name = $post->author?->name;
        if (is_string($name) && $name !== '') {
            return $name;
        }

        $author = data_get($post->meta, 'author.name') ?? data_get($post->meta, 'author');

        return is_string($author) && $author !== '' ? $author : null;
    }

    public function categoryLabel(Post $post): ?string
    {
        $name = $post->category?->name;
        if (is_string($name) && $name !== '') {
            return $name;
        }

        $category = data_get($post->meta, 'category');

        return is_string($category) && $category !== '' ? $category : null;
    }

    public function categoryUrl(Post $post): ?string
    {
        $slug = $post->category?->slug;

        return is_string($slug) && $slug !== ''
            ? route('storefront.cms.categories.show', $slug)
            : null;
    }

    public function authorUrl(Post $post): ?string
    {
        $uuid = $post->author_uuid;

        return is_string($uuid) && $uuid !== ''
            ? route('storefront.cms.authors.show', $uuid)
            : null;
    }

    /**
     * @return array{html: string, toc: list<array{id: string, label: string, level: int}>}
     */
    public function formattedContent(Post $post): array
    {
        return $this->contentFormatter->format($post->content);
    }

    /**
     * @return array{title: string, description: ?string, keywords: ?string, canonical: ?string, ogImage: ?string}
     */
    public function seoMeta(Post $post): array
    {
        $ogImage = $this->featuredImageUrl($post, 'large');
        $meta = $this->cmsSeo->pageMeta(
            Post::SEO_ENTITY_TYPE,
            $post->uuid,
            $post->title,
            $post->excerpt,
        );

        if ($meta['ogImage'] === null && $ogImage !== null) {
            $meta['ogImage'] = $ogImage;
        }

        if ($meta['canonical'] === null && filled($post->slug)) {
            $meta['canonical'] = route('storefront.cms.posts.show', $post->slug);
        }

        return $meta;
    }

    public function seoTitle(Post $post): string
    {
        return $this->seoMeta($post)['title'];
    }

    public function seoDescription(Post $post): ?string
    {
        return $this->seoMeta($post)['description'];
    }

    public function findAuthor(string $uuid): ?User
    {
        return User::query()->where('uuid', $uuid)->first();
    }

    /**
     * @return Builder<Post>
     */
    public function publishedQuery(): Builder
    {
        return Post::query()->where('status', 'published');
    }
}
