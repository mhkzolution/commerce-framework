<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table): void {
            $table->boolean('is_default_shipping')->default(false)->after('is_default');
            $table->boolean('is_default_billing')->default(false)->after('is_default_shipping');
        });

        DB::table('customer_addresses')
            ->where('is_default', true)
            ->whereIn('type', ['shipping', 'both'])
            ->update(['is_default_shipping' => true]);

        DB::table('customer_addresses')
            ->where('is_default', true)
            ->whereIn('type', ['billing', 'both'])
            ->update(['is_default_billing' => true]);
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table): void {
            $table->dropColumn(['is_default_shipping', 'is_default_billing']);
        });
    }
};
