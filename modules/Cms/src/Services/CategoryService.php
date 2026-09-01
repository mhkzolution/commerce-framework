<?php

declare(strict_types=1);

namespace Commerce\Cms\Services;

use Commerce\Cms\DTO\CreateCategoryData;
use Commerce\Cms\DTO\UpdateCategoryData;
use Commerce\Cms\Models\Category;
use Commerce\Cms\Support\CmsSeoSync;
use Commerce\Contracts\Seo\SlugServiceInterface;
use Commerce\Contracts\Seo\UrlRedirectServiceInterface;
use Commerce\Core\Base\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CategoryService extends BaseService
{
    public function __construct(
        private readonly SlugServiceInterface $slugService,
        private readonly UrlRedirectServiceInterface $urlRedirectService,
        private readonly CmsSeoSync $cmsSeo,
    ) {}

    public function create(CreateCategoryData $data): Category
    {
        return DB::transaction(function () use ($data): Category {
            $slug = $this->resolveSlug($data->slug, $data->name);

            $category = Category::query()->create([
                'name' => $data->name,
                'slug' => $slug,
                'description' => $data->description,
                'image_media_uuid' => $data->imageMediaUuid,
                'parent_id' => $data->parentId,
                'is_active' => $data->isActive,
                'position' => $data->position,
            ]);

            $this->slugService->register($slug, Category::SEO_ENTITY_TYPE, $category->uuid, $category->tenant_id);
            $this->cmsSeo->sync(Category::SEO_ENTITY_TYPE, $category->uuid, $data->seo);

            return $category->fresh();
        });
    }

    public function update(Category $category, UpdateCategoryData $data): Category
    {
        return DB::transaction(function () use ($category, $data): Category {
            $previousSlug = $category->slug;
            $slug = $this->resolveSlug($data->slug, $data->name, $category->uuid);

            $category->update([
                'name' => $data->name,
                'slug' => $slug,
                'description' => $data->description,
                'image_media_uuid' => $data->imageMediaUuid,
                'parent_id' => $data->parentId,
                'is_active' => $data->isActive,
                'position' => $data->position,
            ]);

            if ($previousSlug !== $slug) {
                $this->urlRedirectService->createRedirect(
                    "/blog/category/{$previousSlug}",
                    "/blog/category/{$slug}",
                );
                $this->slugService->register($slug, Category::SEO_ENTITY_TYPE, $category->uuid, $category->tenant_id);
            }

            $this->cmsSeo->sync(Category::SEO_ENTITY_TYPE, $category->uuid, $data->seo);

            return $category->fresh();
        });
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }

    public function findActiveBySlug(string $slug): ?Category
    {
        return Category::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    private function resolveSlug(?string $slug, string $name, ?string $ignoreUuid = null): string
    {
        $candidate = filled($slug) ? Str::slug($slug) : Str::slug($name);

        if ($ignoreUuid !== null) {
            $existing = Category::query()->where('slug', $candidate)->where('uuid', '!=', $ignoreUuid)->exists();

            return $existing
                ? $this->slugService->generate($name, Category::SEO_ENTITY_TYPE)
                : $candidate;
        }

        return $this->slugService->isAvailable($candidate, Category::SEO_ENTITY_TYPE)
            ? $candidate
            : $this->slugService->generate($name, Category::SEO_ENTITY_TYPE);
    }
}
