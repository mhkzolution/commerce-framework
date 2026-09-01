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
        if (Schema::hasTable('product_variants')) {
            DB::table('product_variants')->update([
                'price' => DB::raw('price / 100'),
            ]);

            DB::table('product_variants')
                ->whereNotNull('compare_at_price')
                ->update([
                    'compare_at_price' => DB::raw('compare_at_price / 100'),
                ]);
        }

        if (Schema::hasTable('product_variant_price_tiers')) {
            DB::table('product_variant_price_tiers')->update([
                'price' => DB::raw('price / 100'),
            ]);
        }

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->decimal('price', 12, 2)->default(0)->change();
            $table->decimal('compare_at_price', 12, 2)->nullable()->change();
        });

        Schema::table('product_variant_price_tiers', function (Blueprint $table): void {
            $table->decimal('price', 12, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->unsignedBigInteger('price')->default(0)->change();
            $table->unsignedBigInteger('compare_at_price')->nullable()->change();
        });

        Schema::table('product_variant_price_tiers', function (Blueprint $table): void {
            $table->unsignedBigInteger('price')->change();
        });

        if (Schema::hasTable('product_variants')) {
            DB::table('product_variants')->update([
                'price' => DB::raw('ROUND(price * 100)'),
            ]);

            DB::table('product_variants')
                ->whereNotNull('compare_at_price')
                ->update([
                    'compare_at_price' => DB::raw('ROUND(compare_at_price * 100)'),
                ]);
        }

        if (Schema::hasTable('product_variant_price_tiers')) {
            DB::table('product_variant_price_tiers')->update([
                'price' => DB::raw('ROUND(price * 100)'),
            ]);
        }
    }
};
