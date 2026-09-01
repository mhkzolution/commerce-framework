<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table): void {
            $table->unsignedBigInteger('opened_by_user_id')->nullable()->after('opened_by');
            $table->string('closed_by')->nullable()->after('opened_by_user_id');
            $table->unsignedBigInteger('opening_balance')->default(0)->after('closed_by');
            $table->unsignedBigInteger('cash_sales_total')->default(0)->after('opening_balance');
            $table->unsignedBigInteger('expected_cash')->nullable()->after('cash_sales_total');
            $table->unsignedBigInteger('counted_cash')->nullable()->after('expected_cash');
            $table->bigInteger('variance')->nullable()->after('counted_cash');
        });
    }

    public function down(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table): void {
            $table->dropColumn([
                'opened_by_user_id',
                'closed_by',
                'opening_balance',
                'cash_sales_total',
                'expected_cash',
                'counted_cash',
                'variance',
            ]);
        });
    }
};
