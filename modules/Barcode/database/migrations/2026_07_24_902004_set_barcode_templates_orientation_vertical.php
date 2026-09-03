<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('barcode_templates')->update(['label_orientation' => 'vertical']);
    }

    public function down(): void
    {
        DB::table('barcode_templates')->update(['label_orientation' => 'horizontal']);
    }
};
