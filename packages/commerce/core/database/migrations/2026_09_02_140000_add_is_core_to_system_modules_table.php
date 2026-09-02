<?php

declare(strict_types=1);

use Commerce\Core\Modules\SystemModuleCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_modules', function (Blueprint $table): void {
            $table->boolean('is_core')->default(false);
            $table->index('is_core');
        });

        $now = now();

        foreach (SystemModuleCatalog::defaults() as $module) {
            $existing = DB::table('system_modules')->where('code', $module['code'])->first();

            if ($existing !== null) {
                DB::table('system_modules')->where('id', $existing->id)->update([
                    'name' => $module['name'],
                    'description' => $module['description'],
                    'sort_order' => $module['sort_order'],
                    'is_core' => $module['is_core'],
                    'updated_at' => $now,
                ]);

                continue;
            }

            DB::table('system_modules')->insert([
                'code' => $module['code'],
                'name' => $module['name'],
                'description' => $module['description'],
                'status' => $module['status'],
                'sort_order' => $module['sort_order'],
                'is_core' => $module['is_core'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        foreach (SystemModuleCatalog::coreCodes() as $code) {
            DB::table('system_modules')->where('code', $code)->delete();
        }

        Schema::table('system_modules', function (Blueprint $table): void {
            $table->dropColumn('is_core');
        });
    }
};
