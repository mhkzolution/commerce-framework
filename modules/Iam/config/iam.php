<?php

declare(strict_types=1);

return [
    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'suspended' => 'Suspended',
    ],
    'registration_enabled' => false,
    'email_verification_required' => true,
    'super_admin_role' => 'super-admin',
    'two_factor' => [
        'enabled' => env('IAM_TWO_FACTOR_ENABLED', false),
        'required' => env('IAM_TWO_FACTOR_REQUIRED', false),
    ],
    'teams' => [
        'enabled' => false,
    ],
    'impersonation' => [
        'enabled' => true,
        'require_reason' => true,
    ],
    'oauth' => [
        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        ],
        'github' => [
            'client_id' => env('GITHUB_CLIENT_ID'),
            'client_secret' => env('GITHUB_CLIENT_SECRET'),
        ],
    ],
    'api_tokens' => [
        'default_abilities' => ['*'],
        'default_expiry_days' => null,
    ],
    'default_admin' => [
        'name' => 'Super Admin',
        'email' => env('IAM_DEFAULT_ADMIN_EMAIL', 'superadmin@example.com'),
        'password' => env('IAM_DEFAULT_ADMIN_PASSWORD', 'password'),
    ],
];
