<?php

declare(strict_types=1);

namespace Commerce\Cms\Services;

use Commerce\Contracts\Seo\SlugServiceInterface;
use Commerce\Contracts\Seo\UrlRedirectServiceInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Cms\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PostService extends BaseService
{
    public function __construct(
        private readonly SlugServiceInterface $slugService,
        private readonly UrlRedirectServiceInterface $urlRedirectService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Post
    {
        return DB::transaction(function () use ($data): Post {
            $slug = $this->resolveSlug($data['slug'] ?? null, $data['title']);
            $data['slug'] = $slug;
            $data = $this->applyPublishState($data);

            $post = Post::query()->create($data);
            $this->slugService->register($slug, Post::SEO_ENTITY_TYPE, $post->uuid, $post->tenant_id);

            return $post->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Post $post, array $data): Post
    {
        return DB::transaction(function () use ($post, $data): Post {
            $previousSlug = $post->slug;
            $slug = $this->resolveSlug($data['slug'] ?? null, $data['title'], $post->uuid);
            $data['slug'] = $slug;
            $data = $this->applyPublishState($data, $post);

            $post->update($data);

            if ($previousSlug !== $slug) {
                $this->urlRedirectService->createRedirect("/blog/{$previousSlug}", "/blog/{$slug}");
                $this->slugService->register($slug, Post::SEO_ENTITY_TYPE, $post->uuid, $post->tenant_id);
            }

            return $post->fresh();
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
            ->first();
    }

    private function resolveSlug(?string $slug, string $title, ?string $ignoreUuid = null): string
    {
        $candidate = filled($slug) ? Str::slug($slug) : Str::slug($title);

        if ($ignoreUuid !== null) {
            $existing = Post::query()->where('slug', $candidate)->where('uuid', '!=', $ignoreUuid)->exists();

            if ($existing) {
                return $this->slugService->generate($title, Post::SEO_ENTITY_TYPE);
            }

            return $candidate;
        }

        return $this->slugService->isAvailable($candidate, Post::SEO_ENTITY_TYPE)
            ? $candidate
            : $this->slugService->generate($title, Post::SEO_ENTITY_TYPE);
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
