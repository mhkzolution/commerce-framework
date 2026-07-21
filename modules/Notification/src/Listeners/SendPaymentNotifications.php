<?php

declare(strict_types=1);

namespace Commerce\Notification\Listeners;

use Commerce\Contracts\Notification\NotificationDispatcherInterface;
use Commerce\Orders\Models\Order;
use Commerce\Payment\Events\PaymentFailed;
use Commerce\Payment\Events\PaymentPaid;

final class SendPaymentNotifications
{
    public function __construct(
        private readonly NotificationDispatcherInterface $dispatcher,
    ) {}

    public function handlePaid(PaymentPaid $event): void
    {
        $order = Order::query()->where('uuid', $event->orderUuid)->first();
        if ($order === null) {
            return;
        }

        $this->dispatcher->send('payment.received', $order, [
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'amount' => $event->amount,
            'currency' => $event->currency,
        ]);
    }

    public function handleFailed(PaymentFailed $event): void
    {
        $order = Order::query()->where('uuid', $event->orderUuid)->first();
        if ($order === null) {
            return;
        }

        $this->dispatcher->send('payment.failed', $order, [
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
        ]);
    }
}
