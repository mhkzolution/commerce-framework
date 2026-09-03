<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class Ws002HeaderContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_homepage_renders_site_header_primitive(): void
    {
        $html = $this->get('/')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('storefront-site-header', $html);
        $this->assertStringContainsString('storefront-page-container', $html);
        $this->assertStringContainsString('name="search"', $html);
        $this->assertDoesNotMatchRegularExpression(
            '/<header[^>]*>[\s\S]*max-w-5xl[\s\S]*<\/header>/',
            $html,
        );
    }

    public function test_shop_listing_uses_the_same_site_header(): void
    {
        $html = $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('storefront-site-header', $html);
        $this->assertStringContainsString('name="search"', $html);
    }
}
