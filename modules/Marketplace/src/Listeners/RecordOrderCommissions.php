<?php

declare(strict_types=1);

namespace Commerce\Marketplace\Listeners;

use Commerce\Marketplace\Services\CommissionService;
use Commerce\Orders\Events\OrderConfirmed;
use Commerce\Orders\Models\Order;

final class RecordOrderCommissions
{
    public function __construct(
        private readonly CommissionService $commissionService,
    ) {}

    public function __invoke(OrderConfirmed $event): void
    {
        $order = Order::query()
            ->with('lineItems')
            ->where('uuid', $event->orderUuid)
            ->first();

        if ($order === null) {
            return;
        }

        $this->commissionService->recordForOrder($order);
    }
}
