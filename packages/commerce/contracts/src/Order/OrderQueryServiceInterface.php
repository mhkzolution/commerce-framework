<?php

declare(strict_types=1);

namespace Commerce\Contracts\Order;

interface OrderQueryServiceInterface
{
    public function findByUuid(string $uuid): ?object;

    public function findByOrderNumber(string $orderNumber): ?object;

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, object>
     */
    public function paginateForCustomer(string $customerUuid, int $perPage = 25);
}
