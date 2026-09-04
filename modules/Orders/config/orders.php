<?php

declare(strict_types=1);

return [
    'statuses' => [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],
    'default_currency' => 'USD',
    'default_channel' => 'web',
    'default_country' => 'TH',
    'order_number_prefix' => 'ORD-',
    'admin_statuses' => [
        'draft' => 'Draft',
        'pending' => 'Pending',
        'awaiting_payment' => 'Awaiting Payment',
        'paid' => 'Paid',
        'processing' => 'Processing',
    ],
    'channels' => [
        'web' => 'Website',
        'facebook' => 'Facebook',
        'line' => 'LINE',
        'walk-in' => 'Walk-in',
        'phone' => 'Phone Order',
        'other' => 'Other',
    ],
];
