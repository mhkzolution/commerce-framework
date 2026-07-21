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
            $table->uuid('shipping_method_uuid')->nullable()->after('shipping_address');
            $table->string('shipping_method_name')->nullable()->after('shipping_method_uuid');
            $table->bigInteger('shipping_total')->default(0)->after('tax_total');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['shipping_method_uuid', 'shipping_method_name', 'shipping_total']);
        });
    }
};
