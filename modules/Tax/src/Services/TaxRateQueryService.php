<?php

declare(strict_types=1);

namespace Commerce\Tax\Services;

use Commerce\Core\Base\BaseQueryService;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Tax\Models\TaxRate;

final class TaxRateQueryService extends BaseQueryService
{
    public function findByUuid(string $uuid): ?TaxRate
    {
        return TaxRate::query()->where('uuid', $uuid)->first();
    }

    public function paginate(?string $search = null, int $perPage = 25)
    {
        return TaxRate::query()
            ->when($search, fn ($q, string $s) => $q->where(fn ($inner) => $inner
                ->where('name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")))
            ->orderByDesc('priority')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function findOrFail(string $uuid): TaxRate
    {
        $rate = $this->findByUuid($uuid);
        if ($rate === null) {
            throw new EntityNotFoundException("Tax rate [{$uuid}] not found.");
        }

        return $rate;
    }
}
