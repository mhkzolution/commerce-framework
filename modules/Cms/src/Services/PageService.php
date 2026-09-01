<?php

declare(strict_types=1);

namespace Commerce\Cms\Services;

use Commerce\Cms\DTO\CreatePageData;
use Commerce\Cms\DTO\UpdatePageData;
use Commerce\Cms\Models\Page;
use Commerce\Cms\Support\CmsSeoSync;
use Commerce\Contracts\Seo\SlugServiceInterface;
use Commerce\Contracts\Seo\UrlRedirectServiceInterface;
use Commerce\Core\Base\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PageService extends BaseService
{
    public function __construct(
        private readonly SlugServiceInterface $slugService,
        private readonly UrlRedirectServiceInterface $urlRedirectService,
        private readonly CmsSeoSync $cmsSeo,
        private readonly EditorPipeline $editorPipeline,
        private readonly PublishStateResolver $publishState,
    ) {}

    public function create(CreatePageData $data): Page
    {
        $state = $this->publishState->resolve($data->status, $data->publishedAt, $data->unpublishAt);

        return DB::transaction(function () use ($data, $state): Page {
            $slug = $this->resolveSlug($data->slug, $data->title);

            $page = Page::query()->create([
                'title' => $data->title,
                'slug' => $slug,
                'content' => $this->editorPipeline->sanitize($data->content),
                'status' => $state->status,
                'published_at' => $state->publishedAt,
                'unpublish_at' => $state->unpublishAt,
            ]);

            $this->slugService->register($slug, Page::SEO_ENTITY_TYPE, $page->uuid, $page->tenant_id);
            $this->cmsSeo->sync(Page::SEO_ENTITY_TYPE, $page->uuid, $data->seo);

            return $page->fresh();
        });
    }

    public function update(Page $page, UpdatePageData $data): Page
    {
        $state = $this->publishState->resolve($data->status, $data->publishedAt, $data->unpublishAt);

        return DB::transaction(function () use ($page, $data, $state): Page {
            $previousSlug = $page->slug;
            $slug = $this->resolveSlug($data->slug, $data->title, $page->uuid);

            $page->update([
                'title' => $data->title,
                'slug' => $slug,
                'content' => $this->editorPipeline->sanitize($data->content),
                'status' => $state->status,
                'published_at' => $state->publishedAt,
                'unpublish_at' => $state->unpublishAt,
            ]);

            if ($previousSlug !== $slug) {
                $this->urlRedirectService->createRedirect("/pages/{$previousSlug}", "/pages/{$slug}");
                $this->slugService->register($slug, Page::SEO_ENTITY_TYPE, $page->uuid, $page->tenant_id);
            }

            $this->cmsSeo->sync(Page::SEO_ENTITY_TYPE, $page->uuid, $data->seo);

            return $page->fresh();
        });
    }

    public function delete(Page $page): void
    {
        $page->delete();
    }

    public function findPublishedBySlug(string $slug): ?Page
    {
        return Page::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();
    }

    private function resolveSlug(?string $slug, string $title, ?string $ignoreUuid = null): string
    {
        $candidate = filled($slug) ? Str::slug($slug) : Str::slug($title);

        if ($ignoreUuid !== null) {
            $existing = Page::query()->where('slug', $candidate)->where('uuid', '!=', $ignoreUuid)->exists();

            return $existing
                ? $this->slugService->generate($title, Page::SEO_ENTITY_TYPE)
                : $candidate;
        }

        return $this->slugService->isAvailable($candidate, Page::SEO_ENTITY_TYPE)
            ? $candidate
            : $this->slugService->generate($title, Page::SEO_ENTITY_TYPE);
    }
}
