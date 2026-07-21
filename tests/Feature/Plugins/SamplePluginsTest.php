<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use Plugins\HelloWorld\Contracts\GreetingServiceInterface;
use Tests\TestCase;

final class SamplePluginsTest extends TestCase
{
    public function test_hello_world_plugin_binding_is_registered(): void
    {
        $this->assertTrue(app()->bound(GreetingServiceInterface::class));

        $greeting = app(GreetingServiceInterface::class)->greet('Commerce');

        $this->assertSame('Hello from plugin, Commerce!', $greeting);
    }

    public function test_product_badge_hook_adds_markup(): void
    {
        $hooks = app(\Commerce\Contracts\Hook\HookRegistryInterface::class);
        $variant = (object) ['price' => 6000];

        $html = $hooks->filter('storefront.product.card', '', ['variant' => $variant]);

        $this->assertStringContainsString('Premium', $html);
    }
}
