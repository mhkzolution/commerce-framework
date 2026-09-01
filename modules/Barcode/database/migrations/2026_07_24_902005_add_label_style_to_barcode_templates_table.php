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
            $table->decimal('label_padding_top', 8, 2)->default(1)->after('label_orientation');
            $table->decimal('label_padding_right', 8, 2)->default(2)->after('label_padding_top');
            $table->decimal('label_padding_bottom', 8, 2)->default(1)->after('label_padding_right');
            $table->decimal('label_padding_left', 8, 2)->default(2)->after('label_padding_bottom');
            $table->decimal('label_content_gap', 8, 2)->default(0.2)->after('label_padding_left');
            $table->decimal('label_owner_font_size', 8, 2)->default(6)->after('label_content_gap');
            $table->decimal('label_sku_font_size', 8, 2)->default(6)->after('label_owner_font_size');
        });
    }

    public function down(): void
    {
        Schema::table('barcode_templates', function (Blueprint $table): void {
            $table->dropColumn([
                'label_padding_top',
                'label_padding_right',
                'label_padding_bottom',
                'label_padding_left',
                'label_content_gap',
                'label_owner_font_size',
                'label_sku_font_size',
            ]);
        });
    }
};
