<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barcode_templates', function (Blueprint $table): void {
            $table->string('preset_code', 32)->default('a4_40')->after('name');
            $table->boolean('show_name')->default(true)->after('label_sku_font_size');
            $table->boolean('show_sku')->default(true)->after('show_name');
            $table->boolean('show_owner')->default(true)->after('show_sku');
            $table->boolean('show_barcode')->default(true)->after('show_owner');
        });
    }

    public function down(): void
    {
        Schema::table('barcode_templates', function (Blueprint $table): void {
            $table->dropColumn([
                'preset_code',
                'show_name',
                'show_sku',
                'show_owner',
                'show_barcode',
            ]);
        });
    }
};
