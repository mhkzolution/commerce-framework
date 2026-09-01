<?php

declare(strict_types=1);

return [
    'enabled' => env('WEBHOOKS_ENABLED', true),

    'timeout_seconds' => (int) env('WEBHOOKS_TIMEOUT', 10),

    'signature_header' => 'X-Commerce-Signature',

    'events' => [
        'order.created' => 'Order created',
        'order.confirmed' => 'Order confirmed',
        'order.completed' => 'Order completed',
        'order.cancelled' => 'Order cancelled',
        'payment.paid' => 'Payment paid',
        'payment.failed' => 'Payment failed',
        'customer.created' => 'Customer created',
        'product.created' => 'Product created',
        'product.updated' => 'Product updated',
        'product.published' => 'Product published',
        'product.unpublished' => 'Product unpublished',
        'product.archived' => 'Product archived',
        'product.deleted' => 'Product deleted',
    ],
];
