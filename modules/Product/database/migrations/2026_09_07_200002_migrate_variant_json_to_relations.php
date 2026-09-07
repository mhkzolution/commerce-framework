<?php

declare(strict_types=1);

use Commerce\Product\Models\Product;
use Commerce\Product\Services\CatalogVariantRelationMigrator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')
            || ! Schema::hasTable('product_attributes')
            || ! Schema::hasTable('product_attribute_values')
            || ! Schema::hasColumn('product_attribute_values', 'attribute_value_id')) {
            return;
        }

        $migrator = app(CatalogVariantRelationMigrator::class);

        Product::query()
            ->with(['attributeSet.attributes.values', 'variants'])
            ->orderBy('id')
            ->each(static function (Product $product) use ($migrator): void {
                $migrator->migrate($product);
            });
    }

    public function down(): void
    {
        // One-way JSON → relation backfill. up() is idempotent.
    }
};
