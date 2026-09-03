<?php

declare(strict_types=1);

namespace Tests\Feature\Navigation;

use Commerce\Contracts\Navigation\NavigationQueryServiceInterface;
use Commerce\Core\Modules\SystemModuleCatalog;
use Commerce\Navigation\NavigationServiceProvider;
use Tests\TestCase;

final class NavigationHostWiringTest extends TestCase
{
    public function test_catalog_and_commerce_config_enable_navigation(): void
    {
        $codes = array_column(SystemModuleCatalog::defaults(), 'code');

        $this->assertContains('navigation', $codes);
        $this->assertTrue(config('commerce.modules.navigation'));
    }

    public function test_host_boots_navigation_provider_and_admin_route(): void
    {
        $this->assertTrue($this->app->bound(NavigationQueryServiceInterface::class));
        $this->assertNotNull($this->app->getProvider(NavigationServiceProvider::class));
        $this->assertNotNull($this->app['router']->getRoutes()->getByName('admin.navigation.show'));
        $this->assertNotNull($this->app['router']->getRoutes()->getByName('admin.navigation.menus.edit'));
        $this->assertNull($this->app['router']->getRoutes()->getByName('admin.storefront.navigation.show'));
    }
}
