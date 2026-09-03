<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class Ws002FooterTokenContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_storefront_home_and_shop_still_render_existing_footer_chrome(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('storefront-site-footer', false)
            ->assertSee('storefront-site-footer__inner', false)
            ->assertDontSee('x-storefront.layout.page-container', false);

        $this->get('/shop')
            ->assertOk()
            ->assertSee('storefront-site-footer', false)
            ->assertSee('storefront-site-footer__inner', false);
    }
}
