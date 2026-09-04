<?php

declare(strict_types=1);

namespace Commerce\Orders\Services;

use Commerce\Orders\Models\Order;
use Commerce\Orders\Models\OrderEvent;

final class OrderEventRecorder
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function record(
        Order $order,
        string $type,
        string $message,
        ?string $actorUserUuid = null,
        array $meta = [],
    ): OrderEvent {
        return OrderEvent::query()->create([
            'order_id' => $order->id,
            'type' => $type,
            'message' => $message,
            'actor_user_uuid' => $actorUserUuid,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }
}
