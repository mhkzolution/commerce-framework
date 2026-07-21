<?php

declare(strict_types=1);

namespace Commerce\Cms\Services;

use Commerce\Contracts\Seo\SlugServiceInterface;
use Commerce\Contracts\Seo\UrlRedirectServiceInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Cms\Models\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PageService extends BaseService
{
    public function __construct(
        private readonly SlugServiceInterface $slugService,
        private readonly UrlRedirectServiceInterface $urlRedirectService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Page
    {
        return DB::transaction(function () use ($data): Page {
            $slug = $this->resolveSlug($data['slug'] ?? null, $data['title']);
            $data['slug'] = $slug;
            $data = $this->applyPublishState($data);

            $page = Page::query()->create($data);
            $this->slugService->register($slug, Page::SEO_ENTITY_TYPE, $page->uuid, $page->tenant_id);

            return $page->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Page $page, array $data): Page
    {
        return DB::transaction(function () use ($page, $data): Page {
            $previousSlug = $page->slug;
            $slug = $this->resolveSlug($data['slug'] ?? null, $data['title'], $page->uuid);
            $data['slug'] = $slug;
            $data = $this->applyPublishState($data, $page);

            $page->update($data);

            if ($previousSlug !== $slug) {
                $this->urlRedirectService->createRedirect("/pages/{$previousSlug}", "/pages/{$slug}");
                $this->slugService->register($slug, Page::SEO_ENTITY_TYPE, $page->uuid, $page->tenant_id);
            }

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

            if ($existing) {
                return $this->slugService->generate($title, Page::SEO_ENTITY_TYPE);
            }

            return $candidate;
        }

        return $this->slugService->isAvailable($candidate, Page::SEO_ENTITY_TYPE)
            ? $candidate
            : $this->slugService->generate($title, Page::SEO_ENTITY_TYPE);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyPublishState(array $data, ?Page $page = null): array
    {
        return $data;
    }
}
