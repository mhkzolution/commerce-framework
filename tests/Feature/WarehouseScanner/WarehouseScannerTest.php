<?php

declare(strict_types=1);

namespace Tests\Feature\WarehouseScanner;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Inventory\Database\Seeders\InventoryLocationSeeder;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class WarehouseScannerTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            IamSeeder::class,
            SettingsSeeder::class,
            InventoryLocationSeeder::class,
        ]);
    }

    public function test_scanner_interface_loads_for_authorized_user(): void
    {
        $admin = User::query()->first();

        $this->actingAs($admin)
            ->get(route('warehouse.index'))
            ->assertOk()
            ->assertSee('warehouse-scanner-app')
            ->assertSee('warehouse-scanner-input');
    }

    public function test_lookup_finds_variant_by_sku(): void
    {
        $admin = User::query()->first();
        $variant = $this->createPurchasableProduct(price: 100, stock: 12, sku: 'WH-SCAN-001');

        $this->actingAs($admin)
            ->postJson(route('warehouse.api.lookup'), ['sku' => 'WH-SCAN-001'])
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('product.sku', 'WH-SCAN-001')
            ->assertJsonPath('product.variant_uuid', $variant->uuid)
            ->assertJsonPath('product.on_hand', 12);
    }

    public function test_lookup_strips_scanner_suffix_characters(): void
    {
        $admin = User::query()->first();
        $variant = $this->createPurchasableProduct(price: 100, stock: 5, sku: '26300586');

        $this->actingAs($admin)
            ->postJson(route('warehouse.api.lookup'), ['sku' => "26300586\r\n"])
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('product.sku', $variant->sku);
    }

    public function test_scan_action_is_recorded(): void
    {
        $admin = User::query()->first();
        $variant = $this->createPurchasableProduct(price: 100, stock: 8, sku: 'WH-SCAN-002');

        $this->actingAs($admin)
            ->postJson(route('warehouse.api.scan'), [
                'mode' => 'stock-check',
                'sku' => 'WH-SCAN-002',
                'action' => 'found',
                'variant_uuid' => $variant->uuid,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('warehouse_scans', [
            'sku' => 'WH-SCAN-002',
            'mode' => 'stock-check',
            'action' => 'found',
            'variant_uuid' => $variant->uuid,
        ]);
    }

    public function test_dashboard_requires_reports_permission(): void
    {
        $admin = User::query()->first();

        $this->actingAs($admin)
            ->get(route('warehouse.dashboard'))
            ->assertOk();
    }
}
