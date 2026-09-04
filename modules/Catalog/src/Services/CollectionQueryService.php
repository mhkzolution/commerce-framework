<?php

declare(strict_types=1);

namespace Commerce\Catalog\Services;

use Commerce\Catalog\Models\Collection;
use Commerce\Core\Base\BaseQueryService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class CollectionQueryService extends BaseQueryService
{
    public function findByUuid(string $uuid): ?object
    {
        return Collection::query()->where('uuid', $uuid)->first();
    }

    public function findBySlug(string $slug): ?object
    {
        return Collection::query()->where('slug', $slug)->first();
    }

    /**
     * @return LengthAwarePaginator<int, Collection>
     */
    public function paginate(int $perPage = 25)
    {
        return Collection::query()->orderBy('name')->paginate($perPage);
    }
}
