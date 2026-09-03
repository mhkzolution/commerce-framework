<?php

declare(strict_types=1);

namespace Commerce\Core\Modules;

use Commerce\Core\Enums\ModuleStatus;

final class SystemModuleCatalog
{
    /**
     * @return list<array{code: string, name: string, description: string, status: string, sort_order: int, is_core: bool}>
     */
    public static function defaults(): array
    {
        return [
            [
                'code' => 'media',
                'name' => 'Media Library',
                'description' => 'Media library, uploads, and asset management',
                'status' => ModuleStatus::Active->value,
                'sort_order' => 1,
                'is_core' => true,
            ],
            [
                'code' => 'settings',
                'name' => 'Settings',
                'description' => 'Store identity, mail, authentication, and system settings',
                'status' => ModuleStatus::Active->value,
                'sort_order' => 2,
                'is_core' => true,
            ],
            [
                'code' => 'users',
                'name' => 'Users',
                'description' => 'Admin user accounts',
                'status' => ModuleStatus::Active->value,
                'sort_order' => 3,
                'is_core' => true,
            ],
            [
                'code' => 'roles',
                'name' => 'Roles',
                'description' => 'Role definitions and assignments',
                'status' => ModuleStatus::Active->value,
                'sort_order' => 4,
                'is_core' => true,
            ],
            [
                'code' => 'permissions',
                'name' => 'Permissions',
                'description' => 'Permission catalog and grants',
                'status' => ModuleStatus::Active->value,
                'sort_order' => 5,
                'is_core' => true,
            ],
            [
                'code' => 'cms',
                'name' => 'CMS',
                'description' => 'Pages, homepage, banners, and FAQ content',
                'status' => ModuleStatus::Active->value,
                'sort_order' => 10,
                'is_core' => false,
            ],
            [
                'code' => 'blog',
                'name' => 'Blog',
                'description' => 'Blog posts, categories, and tags',
                'status' => ModuleStatus::Active->value,
                'sort_order' => 20,
                'is_core' => false,
            ],
            [
                'code' => 'footer-management',
                'name' => 'Footer Management',
                'description' => 'Storefront footer builder and sections',
                'status' => ModuleStatus::Active->value,
                'sort_order' => 30,
                'is_core' => false,
            ],
            [
                'code' => 'customer-experience',
                'name' => 'Customer Experience',
                'description' => 'Quick view, notifications, and storefront UX extras',
                'status' => ModuleStatus::Active->value,
                'sort_order' => 40,
                'is_core' => false,
            ],
            [
                'code' => 'reviews',
                'name' => 'Reviews',
                'description' => 'Product reviews and ratings',
                'status' => ModuleStatus::Active->value,
                'sort_order' => 50,
                'is_core' => false,
            ],
            [
                'code' => 'marketplace',
                'name' => 'Marketplace',
                'description' => 'Seller portal, commissions, and payouts',
                'status' => ModuleStatus::Active->value,
                'sort_order' => 60,
                'is_core' => false,
            ],
            [
                'code' => 'kyc',
                'name' => 'KYC',
                'description' => 'Know Your Customer verification',
                'status' => ModuleStatus::Active->value,
                'sort_order' => 70,
                'is_core' => false,
            ],
            [
                'code' => 'barcode',
                'name' => 'Barcode',
                'description' => 'Barcode label generation and printing center',
                'status' => ModuleStatus::Active->value,
                'sort_order' => 80,
                'is_core' => false,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function coreCodes(): array
    {
        return array_values(array_map(
            static fn (array $module): string => $module['code'],
            array_filter(self::defaults(), static fn (array $module): bool => $module['is_core']),
        ));
    }

    /**
     * @param  array{code: string, name: string, description: string, status: string, sort_order: int, is_core?: bool}  $module
     * @return array{code: string, name: string, description: string, status: string, sort_order: int}
     */
    public static function withoutCoreFlag(array $module): array
    {
        return [
            'code' => $module['code'],
            'name' => $module['name'],
            'description' => $module['description'],
            'status' => $module['status'],
            'sort_order' => $module['sort_order'],
        ];
    }
}
