<?php

declare(strict_types=1);

namespace Commerce\Core\Database\Seeders;

use Commerce\Core\Models\SystemModule;
use Commerce\Core\Modules\ModuleService;
use Commerce\Core\Modules\SystemModuleCatalog;
use Illuminate\Database\Seeder;

final class SystemModuleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SystemModuleCatalog::defaults() as $module) {
            $existing = SystemModule::query()->where('code', $module['code'])->first();

            if ($existing !== null) {
                $existing->fill([
                    'name' => $module['name'],
                    'description' => $module['description'],
                    'sort_order' => $module['sort_order'],
                    'is_core' => $module['is_core'],
                ])->save();

                continue;
            }

            SystemModule::query()->create($module);
        }

        ModuleService::clearCache();
    }
}
