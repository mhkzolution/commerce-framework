<?php

declare(strict_types=1);

namespace Commerce\Promotion\Services;

use Commerce\Core\Base\BaseQueryService;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Promotion\Models\Promotion;

final class PromotionQueryService extends BaseQueryService
{
    public function findByUuid(string $uuid): ?Promotion
    {
        return Promotion::query()->where('uuid', $uuid)->first();
    }

    public function paginate(?string $search = null, int $perPage = 25)
    {
        return Promotion::query()
            ->when($search, fn ($q, string $s) => $q->where(fn ($inner) => $inner
                ->where('name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")))
            ->latest()
            ->paginate($perPage);
    }

    public function findOrFail(string $uuid): Promotion
    {
        $promotion = $this->findByUuid($uuid);
        if ($promotion === null) {
            throw new EntityNotFoundException("Promotion [{$uuid}] not found.");
        }

        return $promotion;
    }
}
