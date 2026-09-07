<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('backorder_policy', 20)->default('deny')->after('type');
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->boolean('track_inventory')->default(true)->after('sku');
            $table->boolean('sku_is_auto')->default(false)->after('track_inventory');
        });

        DB::table('product_variants')->update(['track_inventory' => false]);

        DB::table('product_variants')
            ->whereExists(function (Builder $query): void {
                $query->selectRaw('1')
                    ->from('inventory_items')
                    ->whereColumn('inventory_items.purchasable_uuid', 'product_variants.uuid');
            })
            ->update(['track_inventory' => true]);
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropColumn(['track_inventory', 'sku_is_auto']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('backorder_policy');
        });
    }
};
