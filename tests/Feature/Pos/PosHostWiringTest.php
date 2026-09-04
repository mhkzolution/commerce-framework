<?php

declare(strict_types=1);

namespace Tests\Feature\Pos;

use Commerce\Core\Features\SystemFeatureCatalog;
use Commerce\Core\Modules\SystemModuleCatalog;
use Commerce\Pos\PosServiceProvider;
use Commerce\WarehouseScanner\WarehouseScannerServiceProvider;
use Tests\TestCase;

final class PosHostWiringTest extends TestCase
{
    public function test_catalog_and_commerce_config_enable_pos_and_warehouse(): void
    {
        $codes = array_column(SystemModuleCatalog::defaults(), 'code');

        $this->assertContains('pos', $codes);
        $this->assertContains('warehouse', $codes);
        $this->assertTrue(config('commerce.modules.pos'));
        $this->assertTrue(config('commerce.modules.warehouse'));
    }

    public function test_feature_catalog_includes_pos_and_warehouse_flags(): void
    {
        $codes = array_column(SystemFeatureCatalog::defaults(), 'code');

        $this->assertContains('pos-hold', $codes);
        $this->assertContains('pos-returns', $codes);
        $this->assertContains('warehouse-reports', $codes);
    }

    public function test_host_boots_pos_and_warehouse_providers(): void
    {
        $this->assertNotNull($this->app->getProvider(PosServiceProvider::class));
        $this->assertNotNull($this->app->getProvider(WarehouseScannerServiceProvider::class));
        $this->assertNotNull($this->app['router']->getRoutes()->getByName('pos.index'));
        $this->assertNotNull($this->app['router']->getRoutes()->getByName('warehouse.index'));
    }

    public function test_pos_and_warehouse_routes_are_module_gated(): void
    {
        $pos = $this->app['router']->getRoutes()->getByName('pos.index');
        $warehouse = $this->app['router']->getRoutes()->getByName('warehouse.index');
        $hold = $this->app['router']->getRoutes()->getByName('pos.api.hold');
        $returns = $this->app['router']->getRoutes()->getByName('pos.returns.index');
        $dashboard = $this->app['router']->getRoutes()->getByName('warehouse.dashboard');
        $history = $this->app['router']->getRoutes()->getByName('warehouse.history');

        $this->assertNotNull($pos);
        $this->assertNotNull($warehouse);
        $this->assertNotNull($hold);
        $this->assertNotNull($returns);
        $this->assertNotNull($dashboard);

        $this->assertContains('module:pos', $pos->gatherMiddleware());
        $this->assertContains('module:warehouse', $warehouse->gatherMiddleware());
        $this->assertContains('feature:pos-hold', $hold->gatherMiddleware());
        $this->assertContains('feature:pos-returns', $returns->gatherMiddleware());
        $this->assertContains('feature:warehouse-reports', $dashboard->gatherMiddleware());
        $this->assertNotNull($history);
        $this->assertContains('feature:warehouse-reports', $history->gatherMiddleware());
    }

    public function test_vite_lists_pos_and_scanner_assets(): void
    {
        $vite = (string) file_get_contents(base_path('vite.config.js'));

        $this->assertStringContainsString('resources/css/pos.css', $vite);
        $this->assertStringContainsString('resources/js/pos/index.js', $vite);
        $this->assertStringContainsString('resources/css/scanner.css', $vite);
        $this->assertStringContainsString('resources/js/scanner/index.js', $vite);
    }

    public function test_admin_nav_points_pos_at_terminal_and_gates_modules(): void
    {
        $admin = require base_path('config/admin.php');
        $sales = collect($admin['navigation'])->firstWhere('id', 'sales');
        $catalog = collect($admin['navigation'])->firstWhere('id', 'catalog');

        $pos = collect($sales['children'])->firstWhere('label', 'POS');
        $scanner = collect($catalog['children'])->firstWhere('label', 'Warehouse Scanner');

        $this->assertSame('pos.index', $pos['route']);
        $this->assertSame('pos', $pos['module']);
        $registers = collect($sales['children'])->firstWhere('label', 'POS Registers');
        $this->assertSame('admin.pos.registers.index', $registers['route']);
        $this->assertSame('pos', $registers['module']);
        $this->assertSame('warehouse.index', $scanner['route']);
        $this->assertSame('warehouse', $scanner['module']);
    }
}
