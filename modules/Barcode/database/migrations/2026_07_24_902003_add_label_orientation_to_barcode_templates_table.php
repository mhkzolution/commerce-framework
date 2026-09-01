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
            $table->string('label_orientation', 16)->default('vertical')->after('label_height');
        });
    }

    public function down(): void
    {
        Schema::table('barcode_templates', function (Blueprint $table): void {
            $table->dropColumn('label_orientation');
        });
    }
};
