<?php

declare(strict_types=1);

return [
    'session_key' => 'commerce.cart',
    'default_currency' => 'USD',
    'auto_confirm_on_checkout' => false,
    'storefront' => [
        'primary_navigation' => [
            'promo_bar' => [
                'enabled' => false,
                'message' => '',
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
                        ],
                    ],
                ],
            ],
        ],
        'search' => [
            'popular_terms' => [
                'sale',
                'new in',
            ],
        ],
        'filters' => [
            'exclude_codes' => ['language', 'lang', 'locale', 'ภาษา'],
            'groups' => [
                'size' => ['size', 'sizes', 'clothing_size', 'shoe_size', 'ขนาด'],
                'color' => ['color', 'colour', 'สี'],
            ],
            'price_presets' => [
                ['label' => '0 – 500', 'min' => 0, 'max' => 500],
                ['label' => '500 – 1,000', 'min' => 500, 'max' => 1000],
                ['label' => '1,000 – 2,000', 'min' => 1000, 'max' => 2000],
            ],
        ],
    ],
];
