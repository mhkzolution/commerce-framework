<?php

declare(strict_types=1);

namespace Commerce\Shipping\Services;

use Commerce\Core\Base\BaseQueryService;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Shipping\Models\ShippingMethod;

final class ShippingMethodQueryService extends BaseQueryService
{
    public function findByUuid(string $uuid): ?ShippingMethod
    {
        return ShippingMethod::query()->where('uuid', $uuid)->first();
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, ShippingMethod>
     */
    public function paginate(?string $search = null, int $perPage = 25)
    {
        return ShippingMethod::query()
            ->when($search, static function ($query, string $search): void {
                $query->where(static function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, ShippingMethod>
     */
    public function activeOrdered()
    {
        return ShippingMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function findOrFail(string $uuid): ShippingMethod
    {
        $method = $this->findByUuid($uuid);

        if ($method === null) {
            throw new EntityNotFoundException("Shipping method [{$uuid}] not found.");
        }

        return $method;
    }
}
