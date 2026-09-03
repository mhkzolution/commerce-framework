<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class Ws002HomepageContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_homepage_renders_without_site_header(): void
    {
        $this->assertFileDoesNotExist(
            resource_path('views/components/storefront/layout/partials/site-header.blade.php'),
        );

        $this->get('/')
            ->assertOk()
            ->assertSee('data-storefront-home', false)
            ->assertSee('storefront-home-arrivals', false)
            ->assertDontSee('x-storefront.layout.partials.site-header', false);
    }

    public function test_homepage_html_uses_page_container(): void
    {
        $html = $this->get('/')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('storefront-page-container', $html);
        $this->assertStringContainsString('storefront-home__inner', $html);
    }
}
