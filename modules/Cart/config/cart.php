<?php

declare(strict_types=1);

return [
    'session_key' => 'commerce.cart',
    'default_currency' => 'THB',
    'auto_confirm_on_checkout' => false,
    'token_enabled' => env('CART_TOKEN_ENABLED', true),
    'token_header' => env('CART_TOKEN_HEADER', 'X-Cart-Token'),
    'token_ttl_days' => (int) env('CART_TOKEN_TTL_DAYS', 30),
    'storefront' => [
        'navigation_cache_ttl' => (int) env('STOREFRONT_NAVIGATION_CACHE_TTL', 3600),
        'primary_navigation' => [
            'promo_bar' => [
                'enabled' => (bool) env('STOREFRONT_PROMO_BAR_ENABLED', false),
                'message' => env('STOREFRONT_PROMO_MESSAGE', ''),
                'dismissible' => true,
            ],
            'items' => [
                [
                    'id' => 'new-in',
                    'label_key' => 'nav_new_in',
                    'type' => 'link',
                    'route' => 'storefront.shop.index',
                    'params' => ['sort' => 'newest'],
                ],
                [
                    'id' => 'shop',
                    'label_key' => 'nav_shop',
                    'type' => 'mega',
                    'columns' => [
                        [
                            'title_key' => 'nav_categories',
                            'source' => 'categories',
                            'limit' => 8,
                            'view_all' => true,
                        ],
                        [
                            'title_key' => 'nav_collections',
                            'source' => 'collections',
                            'limit' => 6,
                            'view_all' => true,
                        ],
                        [
                            'title_key' => 'nav_featured',
                            'links' => [
                                [
                                    'label_key' => 'nav_new_arrivals',
                                    'route' => 'storefront.shop.index',
                                    'params' => ['sort' => 'newest'],
                                ],
                                [
                                    'label_key' => 'sort_price_asc',
                                    'route' => 'storefront.shop.index',
                                    'params' => ['sort' => 'price_asc'],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'brands',
                    'label_key' => 'nav_brands',
                    'type' => 'mega',
                    'columns' => [
                        [
                            'title_key' => 'nav_brands',
                            'source' => 'brands',
                            'limit' => 10,
                            'view_all' => true,
                            'view_all_route' => 'storefront.catalog.brands.index',
                        ],
                    ],
                ],
            ],
        ],
        'search' => [
            'popular_terms' => [
                'รองเท้าวิ่ง',
                'sale',
                'new in',
                'กางเกง',
                'ฮู้ดดี้',
            ],
        ],
        'infinite_scroll' => (bool) env('STOREFRONT_INFINITE_SCROLL', true),
        'delivery_summary' => env('STOREFRONT_DELIVERY_SUMMARY', 'จัดส่งภายใน 2–4 วันทำการ'),
        'payment_methods' => ['visa', 'mastercard', 'promptpay', 'apple_pay'],
        'checkout_payment_methods' => [
            ['id' => 'card', 'icon' => 'card'],
            ['id' => 'promptpay', 'icon' => 'promptpay'],
            ['id' => 'apple_pay', 'icon' => 'apple_pay'],
            ['id' => 'google_pay', 'icon' => 'google_pay'],
            ['id' => 'cod', 'icon' => 'cod'],
            ['id' => 'store_credit', 'icon' => 'store_credit'],
            ['id' => 'gift_card', 'icon' => 'gift_card'],
        ],
        'trust_indicators' => [
            ['key' => 'secure', 'label' => 'Secure checkout'],
            ['key' => 'returns', 'label' => 'Easy returns'],
            ['key' => 'support', 'label' => 'Customer support'],
        ],
        'filters' => [
            'exclude_codes' => ['language', 'lang', 'locale', 'ภาษา'],
            'groups' => [
                'size' => ['size', 'sizes', 'clothing_size', 'shoe_size', 'ขนาด'],
                'color' => ['color', 'colour', 'สี'],
                'age' => ['age', 'age_group', 'อายุ'],
                'gender' => ['gender', 'sex', 'เพศ'],
            ],
            'price_presets' => [
                ['label' => '0 – 500', 'min' => 0, 'max' => 500],
                ['label' => '500 – 1,000', 'min' => 500, 'max' => 1000],
                ['label' => '1,000 – 2,000', 'min' => 1000, 'max' => 2000],
            ],
            'color_map' => [
                'black' => '#1a1a1a',
                'white' => '#ffffff',
                'red' => '#dc2626',
                'blue' => '#2563eb',
                'green' => '#16a34a',
                'yellow' => '#eab308',
                'pink' => '#ec4899',
                'purple' => '#9333ea',
                'orange' => '#ea580c',
                'brown' => '#92400e',
                'grey' => '#6b7280',
                'gray' => '#6b7280',
                'beige' => '#d6c7b0',
                'navy' => '#1e3a5f',
                'gold' => '#ca8a04',
                'silver' => '#9ca3af',
                'cream' => '#f5f0e6',
                'ดำ' => '#1a1a1a',
                'ขาว' => '#ffffff',
                'แดง' => '#dc2626',
                'น้ำเงิน' => '#2563eb',
                'เขียว' => '#16a34a',
                'เหลือง' => '#eab308',
                'ชมพู' => '#ec4899',
                'ม่วง' => '#9333ea',
                'ส้ม' => '#ea580c',
                'น้ำตาล' => '#92400e',
                'เทา' => '#6b7280',
                'ครีม' => '#f5f0e6',
            ],
        ],
    ],
];
