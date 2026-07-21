<?php

declare(strict_types=1);

return [
    'name' => 'Commerce Framework',
    'version' => '1.0.0-alpha',

    'modules' => [
        'iam' => true,
        'settings' => true,
        'media' => true,
        'catalog' => true,
        'cart' => true,
        'product' => true,
        'inventory' => true,
        'customers' => true,
        'orders' => true,
        'payment' => true,
        'shipping' => true,
        'tax' => true,
        'promotion' => true,
        'notification' => true,
        'reports' => true,
        'webhooks' => true,
        'currency' => true,
        'cms' => true,
        'pos' => true,
        'crm' => true,
        'marketplace' => true,
    ],

    'plugins' => [
        'hello-world' => true,
        'product-badge' => true,
    ],

    'tenant' => [
        'enabled' => env('COMMERCE_TENANT_ENABLED', false),
        'header' => 'X-Tenant',
    ],

    'api' => [
        'version' => 'v1',
        'prefix' => 'api',
    ],
];
