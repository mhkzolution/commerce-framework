<?php

declare(strict_types=1);

namespace Tests\Feature\Barcode;

use Commerce\Barcode\BarcodeServiceProvider;
use Commerce\Contracts\Barcode\BarcodeValueGeneratorInterface;
use Commerce\Core\Modules\SystemModuleCatalog;
use Tests\TestCase;

final class BarcodeHostWiringTest extends TestCase
{
    public function test_catalog_and_commerce_config_enable_barcode(): void
    {
        $codes = array_column(SystemModuleCatalog::defaults(), 'code');

        $this->assertContains('barcode', $codes);
        $this->assertTrue(config('commerce.modules.barcode'));
    }

    public function test_host_binds_generator_and_boots_barcode_provider(): void
    {
        $this->assertTrue($this->app->bound(BarcodeValueGeneratorInterface::class));
        $this->assertNotNull($this->app->getProvider(BarcodeServiceProvider::class));
        $this->assertNotNull($this->app['router']->getRoutes()->getByName('admin.barcode.index'));
    }

    public function test_vite_lists_barcode_assets(): void
    {
        $vite = (string) file_get_contents(base_path('vite.config.js'));

        $this->assertStringContainsString('resources/css/barcode.css', $vite);
        $this->assertStringContainsString('resources/js/barcode/index.js', $vite);
        $this->assertStringContainsString('resources/js/barcode/history.js', $vite);
    }

    public function test_generator_exposes_four_strategies(): void
    {
        $generator = $this->app->make(BarcodeValueGeneratorInterface::class);

        $this->assertEqualsCanonicalizing(
            ['random', 'timestamp', 'prefix', 'sequential'],
            $generator->strategies(),
        );
    }
}
