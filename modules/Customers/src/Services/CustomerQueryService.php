<?php

declare(strict_types=1);

namespace Commerce\Customers\Services;

use Commerce\Contracts\Customer\CustomerQueryServiceInterface;
use Commerce\Core\Base\BaseQueryService;
use Commerce\Customers\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class CustomerQueryService extends BaseQueryService implements CustomerQueryServiceInterface
{
    public function findByUuid(string $uuid): ?object
    {
        return Customer::query()->where('uuid', $uuid)->first();
    }

    public function findByEmail(string $email): ?object
    {
        return Customer::query()->where('email', $email)->first();
    }

    /**
     * @return LengthAwarePaginator<int, Customer>
     */
    public function paginate(?string $search = null, ?string $status = null, int $perPage = 25): LengthAwarePaginator
    {
        return Customer::query()
            ->when($status, static fn ($query) => $query->where('status', $status))
            ->when($search, static function ($query, string $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage);
    }
}
