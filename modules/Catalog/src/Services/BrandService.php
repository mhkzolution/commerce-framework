<?php

declare(strict_types=1);

namespace Commerce\Catalog\Services;

use Commerce\Catalog\Contracts\BrandServiceInterface;
use Commerce\Catalog\DTO\CreateBrandData;
use Commerce\Catalog\DTO\UpdateBrandData;
use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Support\SlugGenerator;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\EntityNotFoundException;

final class BrandService extends BaseService implements BrandServiceInterface
{
    public function create(CreateBrandData $data): Brand
    {
        $slug = $data->slug ?? SlugGenerator::unique($data->name, Brand::query());

        return Brand::query()->create([
            'name' => $data->name,
            'slug' => $slug,
            'description' => $data->description,
            'logo_media_uuid' => $data->logoMediaUuid,
            'is_active' => $data->isActive,
        ]);
    }

    public function update(string $uuid, UpdateBrandData $data): Brand
    {
        $brand = $this->findOrFail($uuid);

        $slug = $data->slug ?? SlugGenerator::unique($data->name, Brand::query(), $brand->id);

        $brand->update([
            'name' => $data->name,
            'slug' => $slug,
            'description' => $data->description,
            'logo_media_uuid' => $data->logoMediaUuid,
            'is_active' => $data->isActive,
        ]);

        return $brand->fresh();
    }

    public function delete(string $uuid): void
    {
        $this->findOrFail($uuid)->delete();
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
