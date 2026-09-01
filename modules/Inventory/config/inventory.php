<?php

declare(strict_types=1);

return [
    'movement_types' => [
        'adjustment' => 'Adjustment',
        'receive' => 'Receive',
        'sale' => 'Sale',
        'return' => 'Return',
        'reservation' => 'Reservation',
        'release' => 'Release',
    ],
    'low_stock_threshold' => 5,
    'reserve_on_checkout' => true,
    'purchase_order' => [
        'auto_email_supplier' => (bool) env('INVENTORY_PO_AUTO_EMAIL', false),
        'queue_emails' => (bool) env('INVENTORY_PO_QUEUE_EMAIL', true),
        'queue_name' => env('INVENTORY_PO_QUEUE_NAME', 'notifications'),
    ],
];
