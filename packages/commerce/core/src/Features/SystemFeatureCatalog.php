<?php

declare(strict_types=1);

namespace Commerce\Core\Features;

final class SystemFeatureCatalog
{
    /**
     * @return list<array{code: string, name: string, description: string, module_code: string, default_enabled: bool, sort_order: int, is_core: bool}>
     */
    public static function defaults(): array
    {
        return [
            [
                'code' => 'scheduled-publishing',
                'name' => 'Scheduled Publishing',
                'description' => 'Schedule CMS content for future publish',
                'module_code' => 'cms',
                'default_enabled' => true,
                'sort_order' => 10,
                'is_core' => false,
            ],
            [
                'code' => 'advanced-seo',
                'name' => 'Advanced SEO',
                'description' => 'Advanced SEO fields and controls',
                'module_code' => 'cms',
                'default_enabled' => true,
                'sort_order' => 20,
                'is_core' => false,
            ],
            [
                'code' => 'ai-writer',
                'name' => 'AI Writer',
                'description' => 'AI-assisted content writing',
                'module_code' => 'cms',
                'default_enabled' => true,
                'sort_order' => 30,
                'is_core' => false,
            ],
            [
                'code' => 'review-monitor',
                'name' => 'Review Monitor',
                'description' => 'Monitor and moderate product reviews',
                'module_code' => 'reviews',
                'default_enabled' => true,
                'sort_order' => 40,
                'is_core' => false,
            ],
            [
                'code' => 'pos-hold',
                'name' => 'POS Hold / Resume',
                'description' => 'Hold and resume POS sales',
                'module_code' => 'pos',
                'default_enabled' => true,
                'sort_order' => 50,
                'is_core' => false,
            ],
            [
                'code' => 'pos-returns',
                'name' => 'POS Returns',
                'description' => 'Refund POS orders from the returns workspace',
                'module_code' => 'pos',
                'default_enabled' => true,
                'sort_order' => 60,
                'is_core' => false,
            ],
            [
                'code' => 'warehouse-reports',
                'name' => 'Warehouse Reports',
                'description' => 'Warehouse scanner dashboard and scan history',
                'module_code' => 'warehouse',
                'default_enabled' => true,
                'sort_order' => 70,
                'is_core' => false,
            ],
        ];
    }
}
