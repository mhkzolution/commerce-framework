<?php

declare(strict_types=1);

use Commerce\Core\Enums\FeatureStatus;
use Commerce\Core\Features\SystemFeatureCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $codes = ['pos-hold', 'pos-returns', 'warehouse-reports'];

        foreach (SystemFeatureCatalog::defaults() as $feature) {
            if (! in_array($feature['code'], $codes, true)) {
                continue;
            }

            $existing = DB::table('system_features')->where('code', $feature['code'])->first();

            if ($existing !== null) {
                DB::table('system_features')->where('id', $existing->id)->update([
                    'name' => $feature['name'],
                    'description' => $feature['description'],
                    'module_code' => $feature['module_code'],
                    'sort_order' => $feature['sort_order'],
                    'is_core' => $feature['is_core'],
                    'updated_at' => $now,
                ]);

                continue;
            }

            DB::table('system_features')->insert([
                'code' => $feature['code'],
                'name' => $feature['name'],
                'description' => $feature['description'],
                'module_code' => $feature['module_code'],
                'status' => $feature['default_enabled']
                    ? FeatureStatus::Enabled->value
                    : FeatureStatus::Disabled->value,
                'is_core' => $feature['is_core'],
                'sort_order' => $feature['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('system_features')->whereIn('code', ['pos-hold', 'pos-returns', 'warehouse-reports'])->delete();
    }
};
