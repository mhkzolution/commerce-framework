<?php

declare(strict_types=1);

return [
    'statuses' => [
        'draft' => 'Draft',
        'scheduled' => 'Scheduled',
        'published' => 'Published',
        'archived' => 'Archived',
    ],
    'newsletter' => [
        'enabled' => (bool) env('CMS_NEWSLETTER_ENABLED', true),
    ],
    'homepage' => [
        'cache_ttl' => (int) env('CMS_HOMEPAGE_CACHE_TTL', 600),
    ],
];
