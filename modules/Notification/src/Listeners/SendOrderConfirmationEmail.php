<?php

declare(strict_types=1);

namespace Commerce\Notification\Listeners;

use Commerce\Contracts\Notification\NotificationDispatcherInterface;
use Commerce\Contracts\Order\OrderQueryServiceInterface;
use Commerce\Orders\Events\OrderConfirmed;

final class SendOrderConfirmationEmail
{
    public function __construct(
        private readonly NotificationDispatcherInterface $notifications,
        private readonly OrderQueryServiceInterface $orders,
    ) {}

    public function __invoke(OrderConfirmed $event): void
    {
        if (! (bool) config('notification.send_order_confirmation', true)) {
            return;
        }

        $order = $this->orders->findByUuid($event->orderUuid);
        if ($order === null || empty($order->customer_email)) {
            return;
        }

        $this->notifications->send('order.confirmation', (object) [
            'email' => $order->customer_email,
            'name' => $order->customer_name,
        ], [
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name ?? 'Customer',
            'grand_total' => number_format($order->grand_total / 100, 2),
            'currency' => $order->currency,
            'order' => $order,
        ]);
    }
}
