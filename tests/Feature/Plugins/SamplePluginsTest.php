<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use Commerce\Contracts\Hook\HookRegistryInterface;
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

    public function test_product_badge_shows_new_within_fourteen_days(): void
    {
        $html = $this->badgeHtml(now());

        $this->assertStringContainsString(__('storefront::storefront.new_badge'), $html);
        $this->assertStringContainsString('storefront-product-card__badge', $html);
        $this->assertStringNotContainsString('Premium', $html);
        $this->assertStringNotContainsString('Popular', $html);
    }

    public function test_product_badge_includes_products_created_exactly_fourteen_days_ago(): void
    {
        $html = $this->badgeHtml(now()->subDays(14));

        $this->assertStringContainsString(__('storefront::storefront.new_badge'), $html);
    }

    public function test_product_badge_omits_products_older_than_fourteen_days(): void
    {
        $html = $this->badgeHtml(now()->subDays(15), 'CARD');

        $this->assertSame('CARD', $html);
        $this->assertStringNotContainsString('Premium', $html);
        $this->assertStringNotContainsString('Popular', $html);
    }

    public function test_product_badge_ignores_price(): void
    {
        $hooks = app(HookRegistryInterface::class);

        $html = $hooks->filter('storefront.product.card', '', [
            'product' => (object) [
                'price' => 6000,
                'createdAt' => now()->subDays(15),
            ],
        ]);

        $this->assertSame('', $html);
    }

    private function badgeHtml(mixed $createdAt, string $html = ''): mixed
    {
        $hooks = app(HookRegistryInterface::class);

        return $hooks->filter('storefront.product.card', $html, [
            'product' => (object) ['createdAt' => $createdAt],
        ]);
    }
}
