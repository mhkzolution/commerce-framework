<?php

declare(strict_types=1);

namespace Commerce\Catalog\Services;

use Commerce\Catalog\Contracts\CategoryServiceInterface;
use Commerce\Catalog\DTO\CreateCategoryData;
use Commerce\Catalog\DTO\UpdateCategoryData;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Support\CatalogSeoSync;
use Commerce\Contracts\Seo\SlugServiceInterface;
use Commerce\Contracts\Seo\UrlRedirectServiceInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CategoryService extends BaseService implements CategoryServiceInterface
{
    public function __construct(
        private readonly SlugServiceInterface $slugService,
        private readonly UrlRedirectServiceInterface $urlRedirectService,
        private readonly CatalogSeoSync $catalogSeo,
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
            $this->catalogSeo->sync(Category::SEO_ENTITY_TYPE, $category->uuid, $data->seo);

            return $category->fresh();
        });
    }

    public function update(string $uuid, UpdateCategoryData $data): Category
    {
        return DB::transaction(function () use ($uuid, $data): Category {
            $category = $this->findOrFail($uuid);
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
                $this->urlRedirectService->createRedirect("/categories/{$previousSlug}", "/categories/{$slug}");
                $this->slugService->register($slug, Category::SEO_ENTITY_TYPE, $category->uuid, $category->tenant_id);
            }

            $this->catalogSeo->sync(Category::SEO_ENTITY_TYPE, $category->uuid, $data->seo);

            return $category->fresh();
        });
    }

    public function delete(string $uuid): void
    {
        $this->findOrFail($uuid)->delete();
    }

    public function reorder(string $uuid, int $position): Category
    {
        $category = $this->findOrFail($uuid);
        $category->update(['position' => $position]);

        return $category->fresh();
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
            $exists = Category::query()->where('slug', $candidate)->where('uuid', '!=', $ignoreUuid)->exists();

            return $exists
                ? $this->slugService->generate($name, Category::SEO_ENTITY_TYPE)
                : $candidate;
        }

        return $this->slugService->isAvailable($candidate, Category::SEO_ENTITY_TYPE)
            ? $candidate
            : $this->slugService->generate($name, Category::SEO_ENTITY_TYPE);
    }

    private function findOrFail(string $uuid): Category
    {
        $category = Category::query()->where('uuid', $uuid)->first();

        if ($category === null) {
            throw new EntityNotFoundException("Category [{$uuid}] not found.");
        }

        return $category;
    }
}
