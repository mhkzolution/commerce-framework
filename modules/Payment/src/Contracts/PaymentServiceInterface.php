<?php

declare(strict_types=1);

namespace Commerce\Payment\Contracts;

use Commerce\Payment\Models\Payment;

interface PaymentServiceInterface
{
    public function createForOrder(string $orderUuid, int $amount, string $currency): Payment;

    public function markPaid(string $uuid, ?string $gatewayReference = null): Payment;

    public function markFailed(string $uuid, ?string $reason = null): Payment;

    public function refund(string $uuid, ?int $amount = null): Payment;
}
