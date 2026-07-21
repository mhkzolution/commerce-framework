<?php

declare(strict_types=1);

return [
    'send_order_confirmation' => true,
    'templates' => [
        'order.confirmation' => [
            'subject' => 'Order {{order_number}} confirmed',
            'view' => 'notification::mail.order-confirmation',
        ],
    ],
];
