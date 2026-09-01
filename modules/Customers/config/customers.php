<?php

declare(strict_types=1);

return [
    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'storefront' => [
        'login_modes' => ['email', 'phone', 'otp'],
        'otp' => [
            'enabled' => (bool) env('CUSTOMER_OTP_ENABLED', false),
            'ttl_minutes' => (int) env('CUSTOMER_OTP_TTL_MINUTES', 10),
            'max_attempts' => (int) env('CUSTOMER_OTP_MAX_ATTEMPTS', 5),
        ],
        'sms' => [
            'driver' => env('CUSTOMER_SMS_DRIVER', 'log'),
            'http_url' => env('CUSTOMER_SMS_HTTP_URL'),
            'api_key' => env('CUSTOMER_SMS_API_KEY'),
            'sender' => env('CUSTOMER_SMS_SENDER', 'Commerce'),
        ],
        'registration' => [
            'enabled' => (bool) env('CUSTOMER_REGISTRATION_ENABLED', true),
        ],
        'recaptcha' => [
            'enabled' => (bool) env('RECAPTCHA_ENABLED', false),
            'site_key' => env('RECAPTCHA_SITE_KEY'),
            'secret_key' => env('RECAPTCHA_SECRET_KEY'),
            'min_score' => (float) env('RECAPTCHA_MIN_SCORE', 0.5),
        ],
        'oauth' => [
            'google' => [
                'enabled' => (bool) env('CUSTOMER_OAUTH_GOOGLE_ENABLED', false),
            ],
            'line' => [
                'enabled' => (bool) env('CUSTOMER_OAUTH_LINE_ENABLED', false),
                'channel_id' => env('LINE_CHANNEL_ID'),
                'channel_secret' => env('LINE_CHANNEL_SECRET'),
            ],
            'apple' => [
                'enabled' => (bool) env('CUSTOMER_OAUTH_APPLE_ENABLED', false),
            ],
        ],
        'forgot_password' => [
            'enabled' => (bool) env('CUSTOMER_FORGOT_PASSWORD_ENABLED', true),
        ],
        'support' => [
            'email' => env('STOREFRONT_SUPPORT_EMAIL'),
            'phone' => env('STOREFRONT_SUPPORT_PHONE'),
        ],
    ],
];
