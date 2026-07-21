<?php

declare(strict_types=1);

namespace Commerce\Contracts\Payment;

interface PaymentQueryServiceInterface
{
    public function findByUuid(string $uuid): ?object;

    public function findPendingByOrderUuid(string $orderUuid): ?object;
}
