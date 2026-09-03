<?php

declare(strict_types=1);

use Commerce\Core\Enums\ModuleStatus;
use Commerce\Core\Modules\SystemModuleCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (SystemModuleCatalog::defaults() as $module) {
            if ($module['code'] !== 'navigation') {
                continue;
            }

            $existing = DB::table('system_modules')->where('code', 'navigation')->first();

            if ($existing !== null) {
                DB::table('system_modules')->where('id', $existing->id)->update([
                    'name' => $module['name'],
                    'description' => $module['description'],
                    'sort_order' => $module['sort_order'],
                    'is_core' => $module['is_core'],
                    'updated_at' => $now,
                ]);

                return;
            }

            DB::table('system_modules')->insert([
                'code' => $module['code'],
                'name' => $module['name'],
                'description' => $module['description'],
                'status' => $module['status'] ?? ModuleStatus::Active->value,
                'sort_order' => $module['sort_order'],
                'is_core' => $module['is_core'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('system_modules')->where('code', 'navigation')->delete();
    }
};
