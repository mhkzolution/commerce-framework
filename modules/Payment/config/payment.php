<?php

declare(strict_types=1);

return [
    'statuses' => [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
    ],
    'default_method' => 'manual',
    'simulate_gateway' => true,
    'gateway' => env('PAYMENT_GATEWAY', 'simulated'),
    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
    'confirm_order_on_payment' => true,
    'cancel_order_on_payment_failure' => true,
];
