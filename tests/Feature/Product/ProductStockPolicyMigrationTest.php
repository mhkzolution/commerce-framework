<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Inventory\Models\InventoryItem;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ProductStockPolicyMigrationTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_migration_backfills_track_from_inventory_item_presence(): void
    {
        $migrationPath = base_path(
            'modules/Product/database/migrations/2026_09_07_100000_add_stock_policy_to_products_and_variants.php',
        );

        $this->assertFileExists($migrationPath);

        $migration = require $migrationPath;
        $migration->down();

        $tracked = $this->createPurchasableProduct(sku: 'MIG-TRACK');
        app(InventoryServiceInterface::class)->setOnHand($tracked->uuid, 0);

        $untracked = $this->createPurchasableProduct(sku: 'MIG-FREE');
        InventoryItem::query()
            ->where('purchasable_uuid', $untracked->uuid)
            ->delete();

        $this->assertDatabaseHas('inventory_items', [
            'purchasable_uuid' => $tracked->uuid,
            'on_hand' => 0,
            'reserved' => 0,
        ]);

        $migration->up();

        $this->assertTrue(Schema::hasColumn('products', 'backorder_policy'));
        $this->assertTrue(Schema::hasColumn('product_variants', 'track_inventory'));
        $this->assertTrue(Schema::hasColumn('product_variants', 'sku_is_auto'));
        $this->assertTrue($tracked->fresh()->track_inventory);
        $this->assertFalse($untracked->fresh()->track_inventory);
        $this->assertSame('deny', $tracked->product->fresh()->backorder_policy);
        $this->assertFalse($tracked->fresh()->sku_is_auto);
    }

    public function test_stock_policy_attributes_are_mass_assignable_and_cast(): void
    {
        $product = new Product;
        $product->fill(['backorder_policy' => 'allow']);

        $variant = new ProductVariant;
        $variant->fill([
            'track_inventory' => 0,
            'sku_is_auto' => 1,
        ]);

        $this->assertSame('allow', $product->backorder_policy);
        $this->assertFalse($variant->track_inventory);
        $this->assertTrue($variant->sku_is_auto);
    }
}
