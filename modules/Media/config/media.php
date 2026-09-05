<?php

declare(strict_types=1);

return [
    'disk' => env('MEDIA_DISK', 'public'),
    'path' => 'media',
    'max_upload_size' => 10240, // KB
    'keep_original' => filter_var(env('MEDIA_KEEP_ORIGINAL', true), FILTER_VALIDATE_BOOLEAN),
    'output_format' => 'webp',
    'allowed_mimes' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'application/pdf',
    ],
    'variants' => [
        'thumbnail' => [
            'width' => 300,
            'quality' => 80,
            'format' => 'webp',
        ],
        'card' => [
            'width' => 800,
            'quality' => 80,
            'format' => 'webp',
        ],
        'detail' => [
            'max' => 1600,
            'quality' => 82,
            'format' => 'webp',
        ],
    ],
    'aliases' => [
        'medium' => 'card',
        'large' => 'detail',
    ],
    'srcset' => ['thumbnail', 'card', 'detail'],
    'sizes' => [
        'thumbnail' => '80px',
        'card' => '(min-width: 64rem) 25vw, (min-width: 40rem) 50vw, 100vw',
        'detail' => '(min-width: 64rem) min(50vw, 720px), 100vw',
        'hero' => '100vw',
        'banner' => '(min-width: 64rem) 1240px, 100vw',
        'blog' => '(min-width: 64rem) 720px, 100vw',
        'category' => '160px',
        'cart' => '88px',
    ],
    'size_filters' => [
        'small' => ['max' => 102400],
        'medium' => ['min' => 102400, 'max' => 1048576],
        'large' => ['min' => 1048576],
    ],
    'crop_presets' => [
        'square' => ['ratio' => 1],
        'product' => ['ratio' => 1],
        'banner' => ['ratio' => 3.2],
        'hero' => ['ratio' => 1.7778],
    ],
    'usage_sources' => [
        [
            'key' => 'product',
            'label' => 'Product',
            'table' => 'product_media',
            'column' => 'media_uuid',
            'owner_table' => 'products',
            'owner_key' => 'id',
            'foreign_key' => 'product_id',
            'title' => 'name',
        ],
        [
            'key' => 'category',
            'label' => 'Category',
            'table' => 'categories',
            'column' => 'image_media_uuid',
            'title' => 'name',
        ],
        [
            'key' => 'brand',
            'label' => 'Brand',
            'table' => 'brands',
            'column' => 'logo_media_uuid',
            'title' => 'name',
        ],
        [
            'key' => 'collection',
            'label' => 'Collection',
            'table' => 'collections',
            'column' => 'cover_media_uuid',
            'title' => 'name',
        ],
        [
            'key' => 'blog',
            'label' => 'Blog Post',
            'table' => 'cms_posts',
            'column' => 'featured_image_media_uuid',
            'title' => 'title',
        ],
        [
            'key' => 'cms_category',
            'label' => 'Blog Category',
            'table' => 'cms_categories',
            'column' => 'image_media_uuid',
            'title' => 'name',
        ],
        [
            'key' => 'hero',
            'label' => 'Hero Banner',
            'table' => 'cms_hero_banners',
            'column' => 'image_media_uuid',
        ],
        [
            'key' => 'hero_mobile',
            'label' => 'Hero Banner (mobile)',
            'table' => 'cms_hero_banners',
            'column' => 'mobile_image_media_uuid',
        ],
        [
            'key' => 'promo',
            'label' => 'Promotion Banner',
            'table' => 'cms_promotion_banners',
            'column' => 'image_media_uuid',
            'title' => 'title',
        ],
        [
            'key' => 'seo',
            'label' => 'SEO image',
            'table' => 'seo_entries',
            'column' => 'og_image_media_uuid',
            'title' => 'entity_type',
        ],
    ],
];
