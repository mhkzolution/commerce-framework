<?php

declare(strict_types=1);

namespace Commerce\Orders\Contracts;

use Commerce\Orders\DTO\CreateOrderData;
use Commerce\Orders\Models\Order;

interface OrderServiceInterface
{
    public function create(CreateOrderData $data): Order;

    public function confirm(string $uuid): Order;

    public function complete(string $uuid): Order;

    public function cancel(string $uuid): Order;
}
