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
        'wishlist' => true,
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
        'barcode' => true,
        'warehouse' => true,
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

    'permissions' => [
        'platform.tenant.view' => 'View tenants',
        'platform.tenant.manage' => 'Manage tenants',
    ],

    'channel' => [
        'default' => env('COMMERCE_CHANNEL_DEFAULT', 'web'),
    ],

    'outbox' => [
        'auto_publish' => env('COMMERCE_OUTBOX_AUTO_PUBLISH', true),
        'use_queue' => env('COMMERCE_OUTBOX_USE_QUEUE', false),
        'schedule' => env('COMMERCE_OUTBOX_SCHEDULE', true),
    ],

    'search' => [
        'driver' => env('SEARCH_DRIVER', 'database'),
        'elasticsearch' => [
            'host' => env('ELASTICSEARCH_HOST', ''),
            'index_prefix' => env('ELASTICSEARCH_INDEX_PREFIX', 'commerce'),
        ],
    ],

    'api' => [
        'version' => 'v1',
        'prefix' => 'api',
    ],
];
