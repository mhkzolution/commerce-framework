<?php

declare(strict_types=1);

namespace Commerce\Core\Database\Seeders;

use Commerce\Core\Enums\FeatureStatus;
use Commerce\Core\Features\FeatureService;
use Commerce\Core\Features\SystemFeatureCatalog;
use Commerce\Core\Models\SystemFeature;
use Illuminate\Database\Seeder;

final class SystemFeatureSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SystemFeatureCatalog::defaults() as $feature) {
            $existing = SystemFeature::query()->where('code', $feature['code'])->first();

            if ($existing !== null) {
                $existing->fill([
                    'name' => $feature['name'],
                    'description' => $feature['description'],
                    'module_code' => $feature['module_code'],
                    'sort_order' => $feature['sort_order'],
                    'is_core' => $feature['is_core'],
                ])->save();

                continue;
            }

            SystemFeature::query()->create([
                'code' => $feature['code'],
                'name' => $feature['name'],
                'description' => $feature['description'],
                'module_code' => $feature['module_code'],
                'status' => $feature['default_enabled']
                    ? FeatureStatus::Enabled
                    : FeatureStatus::Disabled,
                'sort_order' => $feature['sort_order'],
                'is_core' => $feature['is_core'],
            ]);
        }

        FeatureService::clearCache();
    }
}
