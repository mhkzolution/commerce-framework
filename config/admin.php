<?php

declare(strict_types=1);

return [
    'name' => env('ADMIN_NAME', config('commerce.name', 'Commerce Framework')),

    'sidebar' => [
        'width' => '16rem',
        'collapsed_width' => '4.5rem',
        'default_collapsed' => false,
        'storage_key' => 'commerce.admin.sidebar.collapsed',
        'groups_storage_key' => 'commerce.admin.sidebar.groups',
    ],

    'theme' => [
        'default' => 'system',
        'storage_key' => 'commerce.admin.theme',
    ],

    'locale' => [
        'default' => 'en',
        'available' => [
            'en' => 'English',
            'th' => 'ไทย',
        ],
        'storage_key' => 'commerce.admin.locale',
    ],

    'command_palette' => [
        'enabled' => true,
        'shortcut' => 'mod+k',
    ],

    'navigation' => [
        [
            'type' => 'link',
            'id' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => 'chart-bar',
            'route' => 'admin.dashboard',
            'order' => 1,
            'permission' => 'reports.dashboard.view',
        ],
        [
            'type' => 'group',
            'id' => 'identity',
            'label' => 'Identity',
            'icon' => 'shield',
            'order' => 10,
            'children' => [
                ['type' => 'link', 'label' => 'Users & Access', 'route' => 'admin.iam.users.index', 'permission' => 'iam.user.view'],
            ],
        ],
        [
            'type' => 'group',
            'id' => 'catalog',
            'label' => 'Catalog',
            'icon' => 'collection',
            'order' => 20,
            'default_open' => true,
            'children' => [
                ['type' => 'link', 'label' => 'Categories', 'route' => 'admin.catalog.index', 'permission' => 'catalog.category.view'],
                ['type' => 'link', 'label' => 'Products', 'route' => 'admin.products.index', 'permission' => 'product.product.view'],
                ['type' => 'link', 'label' => 'Inventory', 'route' => 'admin.inventory.index', 'permission' => 'inventory.stock.view'],
                ['type' => 'link', 'label' => 'Media', 'route' => 'admin.media.index', 'permission' => 'media.media.view'],
            ],
        ],
        [
            'type' => 'group',
            'id' => 'sales',
            'label' => 'Sales',
            'icon' => 'shopping-cart',
            'order' => 30,
            'default_open' => true,
            'children' => [
                ['type' => 'link', 'label' => 'Orders', 'route' => 'admin.orders.index', 'permission' => 'orders.order.view'],
                ['type' => 'link', 'label' => 'Customers', 'route' => 'admin.customers.index', 'permission' => 'customers.customer.view'],
                ['type' => 'link', 'label' => 'Payments', 'route' => 'admin.payments.index', 'permission' => 'payment.payment.view'],
            ],
        ],
        [
            'type' => 'group',
            'id' => 'marketing',
            'label' => 'Marketing',
            'icon' => 'tag',
            'order' => 40,
            'children' => [
                ['type' => 'link', 'label' => 'Promotions', 'route' => 'admin.promotions.index', 'permission' => 'promotion.promotion.view'],
                ['type' => 'link', 'label' => 'Tax rates', 'route' => 'admin.tax.index', 'permission' => 'tax.rate.view'],
            ],
        ],
        [
            'type' => 'group',
            'id' => 'configuration',
            'label' => 'Configuration',
            'icon' => 'cog',
            'order' => 80,
            'children' => [
                ['type' => 'link', 'label' => 'Shipping', 'route' => 'admin.shipping.index', 'permission' => 'shipping.method.view'],
                ['type' => 'link', 'label' => 'Currencies', 'route' => 'admin.currencies.index', 'permission' => 'currency.currency.view'],
                ['type' => 'link', 'label' => 'Webhooks', 'route' => 'admin.webhooks.index', 'permission' => 'webhooks.webhook.view'],
                ['type' => 'link', 'label' => 'Settings', 'route' => 'admin.settings.index', 'permission' => 'settings.setting.view'],
            ],
        ],
    ],
];
