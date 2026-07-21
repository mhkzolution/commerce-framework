<?php

declare(strict_types=1);

namespace Commerce\Cart\Contracts;

use Commerce\Cart\DTO\CheckoutData;
use Commerce\Orders\Models\Order;

interface CheckoutServiceInterface
{
    public function checkout(CheckoutData $data): Order;
}
