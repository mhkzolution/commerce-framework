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

    public function test_homepage_renders_home_landmarks(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-storefront-home', false)
            ->assertSee('storefront-home-main', false);
    }

    public function test_empty_homepage_hides_arrivals_and_emits_seo(): void
    {
        $html = $this->get('/')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('storefront-home-arrivals', $html);
        $this->assertStringContainsString('twitter:card', $html);
        $this->assertStringContainsString('"@type":"WebSite"', $html);
        $this->assertStringContainsString('"@type":"WebPage"', $html);
        $this->assertStringContainsString('"@type":"Organization"', $html);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $html);
        $this->assertStringContainsString('"@type":"SearchAction"', $html);
    }
}
