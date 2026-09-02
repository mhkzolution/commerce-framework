<?php

declare(strict_types=1);

use Commerce\Core\Enums\FeatureStatus;
use Commerce\Core\Features\SystemFeatureCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_features', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('module_code');
            $table->string('status', 20);
            $table->boolean('is_core')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('status');
            $table->index('module_code');
            $table->index('sort_order');
        });

        $now = now();

        foreach (SystemFeatureCatalog::defaults() as $feature) {
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
        Schema::dropIfExists('system_features');
    }
};
