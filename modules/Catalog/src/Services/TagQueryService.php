<?php

declare(strict_types=1);

namespace Commerce\Catalog\Services;

use Commerce\Catalog\Models\Tag;
use Commerce\Contracts\Catalog\TagQueryServiceInterface;
use Commerce\Core\Base\BaseQueryService;

final class TagQueryService extends BaseQueryService implements TagQueryServiceInterface
{
    public function findByUuid(string $uuid): ?object
    {
        return Tag::query()->where('uuid', $uuid)->first();
    }

    public function findBySlug(string $slug): ?object
    {
        return Tag::query()->where('slug', $slug)->first();
    }

    public function findBySlugs(array $slugs): array
    {
        return Tag::query()
            ->whereIn('slug', $slugs)
            ->get()
            ->keyBy('slug')
            ->all();
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Tag>
     */
    public function paginate(int $perPage = 25)
    {
        return Tag::query()->orderBy('name')->paginate($perPage);
    }
}
