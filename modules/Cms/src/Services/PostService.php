<?php

declare(strict_types=1);

namespace Commerce\Cms\Services;

use Commerce\Cms\DTO\CreatePostData;
use Commerce\Cms\DTO\UpdatePostData;
use Commerce\Cms\Models\Post;
use Commerce\Cms\Support\CmsSeoSync;
use Commerce\Cms\Support\UniqueSlug;
use Commerce\Contracts\Seo\SlugServiceInterface;
use Commerce\Contracts\Seo\UrlRedirectServiceInterface;
use Commerce\Core\Base\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PostService extends BaseService
{
    public function __construct(
        private readonly SlugServiceInterface $slugService,
        private readonly UrlRedirectServiceInterface $urlRedirectService,
        private readonly CmsSeoSync $cmsSeo,
        private readonly BlogContentFormatter $contentFormatter,
    ) {}

    public function create(CreatePostData $data): Post
    {
        return DB::transaction(function () use ($data): Post {
            $slug = $this->resolveSlug($data->slug, $data->title);
            $payload = $this->applyPublishState([
                'title' => $data->title,
                'slug' => $slug,
                'excerpt' => $data->excerpt,
                'content' => $data->content,
                'status' => $data->status,
                'published_at' => $data->publishedAt,
                'category_id' => $data->categoryId,
                'author_uuid' => $data->authorUuid,
                'featured_image_media_uuid' => $data->featuredImageMediaUuid,
                'is_featured' => $data->isFeatured,
                'meta' => ['reading_time_minutes' => $this->contentFormatter->readingTimeMinutes($data->content)],
            ]);

            $post = Post::query()->create($payload);
            $post->tags()->sync($data->tagIds);
            $this->slugService->register($slug, Post::SEO_ENTITY_TYPE, $post->uuid, $post->tenant_id);
            $this->cmsSeo->sync(Post::SEO_ENTITY_TYPE, $post->uuid, $data->seo);

            return $post->fresh(['category', 'tags', 'author']);
        });
    }

    public function update(Post $post, UpdatePostData $data): Post
    {
        return DB::transaction(function () use ($post, $data): Post {
            $previousSlug = $post->slug;
            $slug = $this->resolveSlug($data->slug, $data->title, $post->uuid);
            $payload = $this->applyPublishState([
                'title' => $data->title,
                'slug' => $slug,
                'excerpt' => $data->excerpt,
                'content' => $data->content,
                'status' => $data->status,
                'published_at' => $data->publishedAt,
                'category_id' => $data->categoryId,
                'author_uuid' => $data->authorUuid,
                'featured_image_media_uuid' => $data->featuredImageMediaUuid,
                'is_featured' => $data->isFeatured,
                'meta' => array_merge($post->meta ?? [], [
                    'reading_time_minutes' => $this->contentFormatter->readingTimeMinutes($data->content),
                ]),
            ], $post);

            $post->update($payload);
            $post->tags()->sync($data->tagIds);

            if ($previousSlug !== $slug) {
                $this->urlRedirectService->createRedirect("/blog/{$previousSlug}", "/blog/{$slug}");
                $this->slugService->register($slug, Post::SEO_ENTITY_TYPE, $post->uuid, $post->tenant_id);
            }

            $this->cmsSeo->sync(Post::SEO_ENTITY_TYPE, $post->uuid, $data->seo);

            return $post->fresh(['category', 'tags', 'author']);
        });
    }

    public function delete(Post $post): void
    {
        $post->delete();
    }

    public function findPublishedBySlug(string $slug): ?Post
    {
        return Post::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->first();
    }

    private function resolveSlug(?string $slug, string $title, ?string $ignoreUuid = null): string
    {
        $candidate = filled($slug) ? Str::slug($slug) : Str::slug($title);

        return UniqueSlug::allocate($candidate, function (string $value) use ($ignoreUuid): bool {
            return Post::query()
                ->where('slug', $value)
                ->when($ignoreUuid, static fn ($query) => $query->where('uuid', '!=', $ignoreUuid))
                ->exists();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyPublishState(array $data, ?Post $post = null): array
    {
        if (($data['status'] ?? '') === 'published' && empty($data['published_at']) && ($post === null || $post->published_at === null)) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
