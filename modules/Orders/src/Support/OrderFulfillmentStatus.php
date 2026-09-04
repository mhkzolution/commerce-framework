<?php

declare(strict_types=1);

namespace Commerce\Orders\Support;

use Commerce\Orders\Models\Order;
use Commerce\Orders\Models\OrderShipment;

final class OrderFulfillmentStatus
{
    public const UNFULFILLED = 'unfulfilled';

    public const PARTIAL = 'partial';

    public const FULFILLED = 'fulfilled';

    public const CANCELLED = 'cancelled';

    /**
     * @param  array<int, int>  $shippedByLineId
     */
    public static function fromOrder(Order $order, array $shippedByLineId): string
    {
        if ($order->isCancelled()) {
            return self::CANCELLED;
        }

        $ordered = 0;
        $shipped = 0;

        foreach ($order->lineItems as $line) {
            $qty = (int) $line->quantity;
            $ordered += $qty;
            $shipped += min($qty, $shippedByLineId[(int) $line->id] ?? 0);
        }

        if ($ordered === 0 || $shipped <= 0) {
            return self::UNFULFILLED;
        }

        if ($shipped < $ordered) {
            return self::PARTIAL;
        }

        return self::FULFILLED;
    }

    public static function isOpenShipment(OrderShipment $shipment): bool
    {
        return ! $shipment->isCancelled();
    }
}
