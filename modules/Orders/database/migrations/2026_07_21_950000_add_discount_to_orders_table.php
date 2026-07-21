<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->bigInteger('discount_total')->default(0)->after('subtotal');
            $table->uuid('promotion_uuid')->nullable()->after('discount_total');
            $table->string('promotion_code')->nullable()->after('promotion_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['discount_total', 'promotion_uuid', 'promotion_code']);
        });
    }
};
