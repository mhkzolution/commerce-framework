<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->string('barcode', 100)->nullable()->after('sku');
            $table->decimal('cost', 12, 2)->nullable()->after('compare_at_price');
            $table->decimal('weight', 12, 3)->nullable()->after('cost');
            $table->string('status', 20)->default('active')->after('weight');

            $table->unique(['tenant_id', 'barcode']);
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropUnique(['tenant_id', 'barcode']);
            $table->dropColumn(['barcode', 'cost', 'weight', 'status']);
        });
    }
};
