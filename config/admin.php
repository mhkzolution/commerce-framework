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
        'default' => 'th',
        'available' => [
            'th' => 'ไทย',
            'en' => 'English',
        ],
        'session_key' => 'commerce.locale',
        'storage_key' => 'commerce.admin.locale',
    ],

    'command_palette' => [
        'enabled' => true,
        'shortcut' => 'mod+k',
    ],

    'navigation' => [
        [
            'type' => 'group',
            'id' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => 'chart-bar',
            'order' => 10,
            'default_open' => true,
            'children' => [
                ['type' => 'link', 'label' => 'Dashboard', 'route' => 'admin.dashboard', 'permission' => 'reports.dashboard.view'],
            ],
        ],
        [
            'type' => 'group',
            'id' => 'sales',
            'label' => 'Sales',
            'icon' => 'shopping-cart',
            'order' => 20,
            'default_open' => true,
            'children' => [
                ['type' => 'link', 'label' => 'Orders', 'route' => 'admin.orders.index', 'permission' => 'orders.order.view'],
                ['type' => 'link', 'label' => 'Customers', 'route' => 'admin.customers.index', 'permission' => 'customers.customer.view'],
                ['type' => 'link', 'label' => 'Payments', 'route' => 'admin.payments.index', 'permission' => 'payment.payment.view'],
                ['type' => 'link', 'label' => 'POS', 'route' => 'admin.pos.registers.index', 'permission' => 'pos.register.view'],
            ],
        ],
        [
            'type' => 'group',
            'id' => 'catalog',
            'label' => 'Catalog',
            'icon' => 'cube',
            'order' => 30,
            'default_open' => true,
            'children' => [
                ['type' => 'link', 'label' => 'Products', 'route' => 'admin.products.index', 'permission' => 'product.product.view'],
                ['type' => 'link', 'label' => 'Categories', 'route' => 'admin.catalog.index', 'permission' => 'catalog.category.view'],
                ['type' => 'link', 'label' => 'Inventory', 'route' => 'admin.inventory.index', 'permission' => 'inventory.stock.view'],
                ['type' => 'link', 'label' => 'Media', 'route' => 'admin.media.index', 'permission' => 'media.media.view'],
                ['type' => 'link', 'label' => 'Barcode Center', 'route' => 'admin.barcode.index', 'permission' => 'barcode.print'],
                ['type' => 'link', 'label' => 'Warehouse Scanner', 'route' => 'warehouse.index', 'permission' => 'warehouse.scan'],
                ['type' => 'link', 'label' => 'Product Settings', 'route' => 'admin.products.settings.show', 'permission' => 'product.product.view'],
            ],
        ],
        [
            'type' => 'group',
            'id' => 'marketing',
            'label' => 'Marketing',
            'icon' => 'megaphone',
            'order' => 40,
            'children' => [
                ['type' => 'link', 'label' => 'Promotions', 'route' => 'admin.promotions.index', 'permission' => 'promotion.promotion.view'],
                ['type' => 'link', 'label' => 'CRM', 'route' => 'admin.crm.leads.index', 'permission' => 'crm.lead.view'],
                ['type' => 'link', 'label' => 'Marketplace', 'route' => 'admin.marketplace.sellers.index', 'permission' => 'marketplace.seller.view'],
            ],
        ],
        [
            'type' => 'group',
            'id' => 'website',
            'label' => 'Website',
            'icon' => 'globe-alt',
            'order' => 50,
            'children' => [
                ['type' => 'link', 'label' => 'Storefront', 'route' => 'admin.settings.appearance.show', 'permission' => 'settings.setting.view'],
                ['type' => 'link', 'label' => 'Navigation', 'route' => 'admin.storefront.navigation.show', 'permission' => 'settings.setting.view'],
                ['type' => 'link', 'label' => 'Customer Experience', 'route' => 'admin.settings.customer-experience.show', 'permission' => 'settings.setting.view'],
                ['type' => 'link', 'label' => 'Footer', 'route' => 'admin.settings.footer.show', 'permission' => 'settings.setting.view'],
            ],
        ],
        [
            'type' => 'group',
            'id' => 'content',
            'label' => 'Content',
            'icon' => 'document-text',
            'order' => 55,
            'default_open' => true,
            'children' => [
                ['type' => 'link', 'label' => 'Posts', 'route' => 'admin.cms.posts.index', 'permission' => 'cms.post.view'],
                ['type' => 'link', 'label' => 'Categories', 'route' => 'admin.cms.categories.index', 'permission' => 'cms.category.view'],
                ['type' => 'link', 'label' => 'Tags', 'route' => 'admin.cms.tags.index', 'permission' => 'cms.tag.view'],
                ['type' => 'link', 'label' => 'Pages', 'route' => 'admin.cms.pages.index', 'permission' => 'cms.page.view'],
            ],
        ],
        [
            'type' => 'group',
            'id' => 'reports',
            'label' => 'Reports',
            'icon' => 'presentation-chart-line',
            'order' => 60,
            'children' => [
                ['type' => 'link', 'label' => 'Overview', 'route' => 'admin.reports.index', 'permission' => 'reports.dashboard.view'],
                ['type' => 'link', 'label' => 'Sales Reports', 'route' => 'admin.reports.sales.index', 'permission' => 'reports.dashboard.view'],
                ['type' => 'link', 'label' => 'Order Reports', 'route' => 'admin.reports.orders.index', 'permission' => 'reports.dashboard.view'],
                ['type' => 'link', 'label' => 'Product Reports', 'route' => 'admin.reports.products.index', 'permission' => 'reports.dashboard.view'],
            ],
        ],
        [
            'type' => 'group',
            'id' => 'identity',
            'label' => 'Users & Access',
            'icon' => 'users',
            'order' => 70,
            'children' => [
                ['type' => 'link', 'label' => 'Users', 'route' => 'admin.iam.users.index', 'permission' => 'iam.user.view'],
                ['type' => 'link', 'label' => 'Roles', 'route' => 'admin.iam.roles.index', 'permission' => 'iam.role.view'],
                ['type' => 'link', 'label' => 'Permissions', 'route' => 'admin.iam.permissions.index', 'permission' => 'iam.permission.view'],
                ['type' => 'link', 'label' => 'Teams', 'route' => 'admin.iam.teams.index', 'permission' => 'iam.team.view'],
                ['type' => 'link', 'label' => 'Activity Logs', 'route' => 'admin.iam.audit-logs.index', 'permission' => 'iam.audit.view'],
                ['type' => 'link', 'label' => 'Security', 'route' => 'admin.iam.security.show'],
            ],
        ],
        [
            'type' => 'group',
            'id' => 'configuration',
            'label' => 'Settings',
            'icon' => 'cog',
            'order' => 80,
            'children' => [
                ['type' => 'link', 'label' => 'Website Settings', 'route' => 'admin.settings.site-identity.show', 'permission' => 'settings.setting.view'],
                ['type' => 'link', 'label' => 'Email', 'route' => 'admin.settings.mail.show', 'permission' => 'settings.setting.view'],
                ['type' => 'link', 'label' => 'Login & Security', 'route' => 'admin.settings.auth.show', 'permission' => 'settings.setting.view'],
                ['type' => 'link', 'label' => 'Languages', 'route' => 'admin.settings.translations.index', 'permission' => 'settings.setting.view'],
                ['type' => 'link', 'label' => 'Currency', 'route' => 'admin.currencies.index', 'permission' => 'currency.currency.view'],
                ['type' => 'link', 'label' => 'Tax', 'route' => 'admin.tax.index', 'permission' => 'tax.rate.view'],
                ['type' => 'link', 'label' => 'Shipping', 'route' => 'admin.shipping.index', 'permission' => 'shipping.method.view'],
                ['type' => 'link', 'label' => 'Webhooks', 'route' => 'admin.webhooks.index', 'permission' => 'webhooks.webhook.view'],
                ['type' => 'link', 'label' => 'Notifications', 'route' => 'admin.notification.templates.index', 'permission' => 'notification.template.view'],
                ['type' => 'link', 'label' => 'System Settings', 'route' => 'admin.settings.index', 'permission' => 'settings.setting.view'],
            ],
        ],
        [
            'type' => 'group',
            'id' => 'platform',
            'label' => 'Platform',
            'icon' => 'building-office-2',
            'order' => 90,
            'children' => [
                ['type' => 'link', 'label' => 'Tenants', 'route' => 'admin.platform.tenants.index', 'permission' => 'platform.tenant.view'],
            ],
        ],
    ],
];
