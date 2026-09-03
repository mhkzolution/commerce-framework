<?php

declare(strict_types=1);

namespace Tests\Unit\Cart;

use Commerce\Cart\DTO\HomepageNavigationData;
use Commerce\Cart\DTO\HomepageProductCardData;
use Tests\TestCase;

final class HomepageDtoRenderingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_product_slides_render_homepage_product_card_data(): void
    {
        $card = new HomepageProductCardData(
            uuid: '11111111-1111-1111-1111-111111111111',
            name: 'DTO Harbor Mug',
            slug: 'dto-harbor-mug',
            url: '/products/dto-harbor-mug',
            variantUuid: '22222222-2222-2222-2222-222222222222',
            price: 1250,
            compareAtPrice: null,
            imageUrl: 'https://cdn.test/mug.jpg',
            available: 4,
            inStock: true,
        );

        $html = view('cart::storefront.partials.home-product-slides', [
            'arrivalProducts' => [$card],
            'displayCurrency' => 'THB',
        ])->render();

        $this->assertStringContainsString('DTO Harbor Mug', $html);
        $this->assertStringContainsString('/products/dto-harbor-mug', $html);
        $this->assertStringContainsString('https://cdn.test/mug.jpg', $html);
        $this->assertStringContainsString('12.50', $html);
        $this->assertStringContainsString('THB', $html);
        $this->assertStringContainsString(__('storefront::storefront.in_stock'), $html);
        $this->assertStringContainsString('storefront-home-product-card', $html);
        $this->assertStringNotContainsString('defaultVariant', $html);
    }

    public function test_arrival_tabs_render_homepage_navigation_data(): void
    {
        $tab = new HomepageNavigationData(
            uuid: '33333333-3333-3333-3333-333333333333',
            name: 'DTO Mugs',
            slug: 'dto-mugs',
            url: '/shop?category=dto-mugs',
        );

        $html = view('cart::storefront.partials.home-section-arrivals', [
            'arrivalCategories' => [$tab],
            'arrivalProducts' => [],
            'activeArrivalCategory' => 'dto-mugs',
            'displayCurrency' => 'THB',
        ])->render();

        $this->assertStringContainsString('DTO Mugs', $html);
        $this->assertStringContainsString('data-category="dto-mugs"', $html);
        $this->assertStringContainsString('storefront-home-arrivals', $html);
    }

    public function test_out_of_stock_card_renders_dto_flag(): void
    {
        $card = new HomepageProductCardData(
            uuid: '44444444-4444-4444-4444-444444444444',
            name: 'Sold Out Tray',
            slug: 'sold-out-tray',
            url: '/products/sold-out-tray',
            variantUuid: '55555555-5555-5555-5555-555555555555',
            price: 900,
            compareAtPrice: null,
            imageUrl: null,
            available: 0,
            inStock: false,
        );

        $html = view('cart::storefront.partials.home-product-card', [
            'product' => $card,
            'displayCurrency' => 'THB',
            'priority' => false,
        ])->render();

        $this->assertStringContainsString('Sold Out Tray', $html);
        $this->assertStringContainsString(__('storefront::storefront.out_of_stock'), $html);
        $this->assertStringNotContainsString(__('storefront::storefront.in_stock'), $html);
    }
}
