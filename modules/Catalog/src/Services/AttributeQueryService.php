<?php

declare(strict_types=1);

namespace Commerce\Catalog\Services;

use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeSet;
use Commerce\Contracts\Catalog\AttributeQueryServiceInterface;
use Commerce\Core\Base\BaseQueryService;

final class AttributeQueryService extends BaseQueryService implements AttributeQueryServiceInterface
{
    public function findByUuid(string $uuid): ?object
    {
        return Attribute::query()->where('uuid', $uuid)->first();
    }

    public function findByCode(string $code): ?object
    {
        return Attribute::query()->where('code', $code)->first();
    }

    public function forAttributeSet(string $setUuid): array
    {
        $set = AttributeSet::query()->where('uuid', $setUuid)->first();

        if ($set === null) {
            return [];
        }

        return $set->attributes()->get()->all();
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Attribute>
     */
    public function paginate(int $perPage = 25)
    {
        return Attribute::query()->orderBy('position')->orderBy('name')->paginate($perPage);
    }

    /**
     * @return list<Attribute>
     */
    public function all(): array
    {
        return Attribute::query()->orderBy('position')->orderBy('name')->get()->all();
    }
}
