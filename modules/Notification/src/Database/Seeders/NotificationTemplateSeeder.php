<?php

declare(strict_types=1);

namespace Commerce\Notification\Database\Seeders;

use Commerce\Notification\Services\NotificationTemplateService;
use Illuminate\Database\Seeder;

final class NotificationTemplateSeeder extends Seeder
{
    public function run(NotificationTemplateService $templates): void
    {
        $templates->upsert(
            'order.confirmation',
            'Order Confirmation',
            'Order {{order_number}} confirmed',
            'notification::mail.order-confirmation',
        );

        $templates->upsert(
            'order.created',
            'Order Created',
            'We received your order {{order_number}}',
            'notification::mail.order-created',
        );

        $templates->upsert(
            'payment.received',
            'Payment Received',
            'Payment received for order {{order_number}}',
            'notification::mail.payment-received',
        );

        $templates->upsert(
            'payment.failed',
            'Payment Failed',
            'Payment failed for order {{order_number}}',
            'notification::mail.payment-failed',
        );
    }
}
