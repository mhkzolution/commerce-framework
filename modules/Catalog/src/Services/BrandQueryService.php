<?php

declare(strict_types=1);

namespace Commerce\Catalog\Services;

use Commerce\Catalog\Models\Brand;
use Commerce\Contracts\Catalog\BrandQueryServiceInterface;
use Commerce\Core\Base\BaseQueryService;

final class BrandQueryService extends BaseQueryService implements BrandQueryServiceInterface
{
    public function findByUuid(string $uuid): ?object
    {
        return Brand::query()->where('uuid', $uuid)->first();
    }

    public function findBySlug(string $slug): ?object
    {
        return Brand::query()->where('slug', $slug)->first();
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Brand>
     */
    public function paginate(int $perPage = 25)
    {
        return Brand::query()->orderBy('name')->paginate($perPage);
    }
}
