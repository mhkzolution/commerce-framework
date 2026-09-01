<?php

declare(strict_types=1);

namespace Commerce\Catalog\Services;

use Commerce\Catalog\Contracts\BrandServiceInterface;
use Commerce\Catalog\DTO\CreateBrandData;
use Commerce\Catalog\DTO\UpdateBrandData;
use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Support\CatalogSeoSync;
use Commerce\Contracts\Seo\SlugServiceInterface;
use Commerce\Contracts\Seo\UrlRedirectServiceInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class BrandService extends BaseService implements BrandServiceInterface
{
    public function __construct(
        private readonly SlugServiceInterface $slugService,
        private readonly UrlRedirectServiceInterface $urlRedirectService,
        private readonly CatalogSeoSync $catalogSeo,
    ) {}

    public function create(CreateBrandData $data): Brand
    {
        return DB::transaction(function () use ($data): Brand {
            $slug = $this->resolveSlug($data->slug, $data->name);

            $brand = Brand::query()->create([
                'name' => $data->name,
                'slug' => $slug,
                'description' => $data->description,
                'logo_media_uuid' => $data->logoMediaUuid,
                'is_active' => $data->isActive,
            ]);

            $this->slugService->register($slug, Brand::SEO_ENTITY_TYPE, $brand->uuid, $brand->tenant_id);
            $this->catalogSeo->sync(Brand::SEO_ENTITY_TYPE, $brand->uuid, $data->seo);

            return $brand->fresh();
        });
    }

    public function update(string $uuid, UpdateBrandData $data): Brand
    {
        return DB::transaction(function () use ($uuid, $data): Brand {
            $brand = $this->findOrFail($uuid);
            $previousSlug = $brand->slug;
            $slug = $this->resolveSlug($data->slug, $data->name, $brand->uuid);

            $brand->update([
                'name' => $data->name,
                'slug' => $slug,
                'description' => $data->description,
                'logo_media_uuid' => $data->logoMediaUuid,
                'is_active' => $data->isActive,
            ]);

            if ($previousSlug !== $slug) {
                $this->urlRedirectService->createRedirect("/brands/{$previousSlug}", "/brands/{$slug}");
                $this->slugService->register($slug, Brand::SEO_ENTITY_TYPE, $brand->uuid, $brand->tenant_id);
            }

            $this->catalogSeo->sync(Brand::SEO_ENTITY_TYPE, $brand->uuid, $data->seo);

            return $brand->fresh();
        });
    }

    public function delete(string $uuid): void
    {
        $this->findOrFail($uuid)->delete();
    }

    public function findActiveBySlug(string $slug): ?Brand
    {
        return Brand::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    private function resolveSlug(?string $slug, string $name, ?string $ignoreUuid = null): string
    {
        $candidate = filled($slug) ? Str::slug($slug) : Str::slug($name);

        if ($ignoreUuid !== null) {
            $exists = Brand::query()->where('slug', $candidate)->where('uuid', '!=', $ignoreUuid)->exists();

            return $exists
                ? $this->slugService->generate($name, Brand::SEO_ENTITY_TYPE)
                : $candidate;
        }

        return $this->slugService->isAvailable($candidate, Brand::SEO_ENTITY_TYPE)
            ? $candidate
            : $this->slugService->generate($name, Brand::SEO_ENTITY_TYPE);
    }

    private function findOrFail(string $uuid): Brand
    {
        $brand = Brand::query()->where('uuid', $uuid)->first();

        if ($brand === null) {
            throw new EntityNotFoundException("Brand [{$uuid}] not found.");
        }

        return $brand;
    }
}
