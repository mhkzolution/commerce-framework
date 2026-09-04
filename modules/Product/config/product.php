<?php

declare(strict_types=1);

return [
    'types' => [
        'simple' => 'Simple',
        'variable' => 'Variable',
    ],
    'statuses' => [
        'draft' => 'Draft',
        'scheduled' => 'Scheduled',
        'published' => 'Published',
        'archived' => 'Archived',
    ],
    'visibilities' => [
        'public' => 'Public',
        'hidden' => 'Hidden',
    ],
    'attribute_option_presets' => [],
    'default_attribute_set_code' => env('PRODUCT_DEFAULT_ATTRIBUTE_SET_CODE', ''),
    'variant_presets' => [
        'attribute_set_code' => 'variant_presets',
        'attribute_set_name' => 'Variant options',
    ],
    'import' => [
        'woocommerce' => [
            'attribute_set_code' => env('PRODUCT_DEFAULT_ATTRIBUTE_SET_CODE', 'woocommerce_default'),
            'attribute_set_name' => 'WooCommerce Default',
            'wordpress_uploads_disk' => env('WORDPRESS_UPLOADS_DISK', 'wordpress_uploads'),
        ],
    ],
];
