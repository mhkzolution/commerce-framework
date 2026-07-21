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
        'enabled' => false,
        'required' => false,
    ],
    'teams' => [
        'enabled' => false,
    ],
    'impersonation' => [
        'enabled' => true,
        'require_reason' => true,
    ],
    'default_admin' => [
        'name' => 'Super Admin',
        'email' => env('IAM_DEFAULT_ADMIN_EMAIL', 'superadmin@example.com'),
        'password' => env('IAM_DEFAULT_ADMIN_PASSWORD', 'password'),
    ],
];
