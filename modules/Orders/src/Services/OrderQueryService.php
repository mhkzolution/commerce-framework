<?php

declare(strict_types=1);

namespace Commerce\Orders\Services;

use Commerce\Contracts\Order\OrderQueryServiceInterface;
use Commerce\Core\Base\BaseQueryService;
use Commerce\Orders\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class OrderQueryService extends BaseQueryService implements OrderQueryServiceInterface
{
    public function findByUuid(string $uuid, array $relations = []): ?object
    {
        return Order::query()
            ->when($relations !== [], static fn ($query) => $query->with($relations))
            ->where('uuid', $uuid)
            ->first();
    }

    public function findByOrderNumber(string $orderNumber, array $relations = []): ?object
    {
        return Order::query()
            ->when($relations !== [], static fn ($query) => $query->with($relations))
            ->where('order_number', $orderNumber)
            ->first();
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginate(
        ?string $search = null,
        ?string $status = null,
        int $perPage = 25,
        array $relations = [],
        ?string $channel = null,
    ): LengthAwarePaginator {
        return Order::query()
            ->when($relations !== [], static fn ($query) => $query->with($relations))
            ->when($status, static fn ($query) => $query->where('status', $status))
            ->when($channel, static fn ($query) => $query->where('channel', $channel))
            ->when($search, static function ($query, string $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('order_number', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginateForCustomer(string $customerUuid, int $perPage = 25): LengthAwarePaginator
    {
        return Order::query()
            ->with('lineItems')
            ->where('customer_uuid', $customerUuid)
            ->latest()
            ->paginate($perPage);
    }
}
