<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Cart\Services\StorefrontPrimaryNavigation;
use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class StorefrontHeaderMegaLinkTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_shop_and_brands_header_items_link_to_their_pages(): void
    {
        $this->seedCatalog();

        $nav = app(StorefrontPrimaryNavigation::class)->build();
        $shop = collect($nav['items'])->firstWhere('id', 'shop');
        $brands = collect($nav['items'])->firstWhere('id', 'brands');

        $this->assertIsArray($shop);
        $this->assertSame(route('storefront.shop.index'), $shop['url']);
        $this->assertIsArray($brands);
        $this->assertSame(route('storefront.brands.index'), $brands['url']);

        $html = $this->get('/')
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<a[^>]+href="'.preg_quote(route('storefront.shop.index'), '/').'"[^>]+data-mega-menu-trigger="shop"/',
            $html,
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<button[^>]+data-mega-menu-trigger="shop"/',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/<a[^>]+href="'.preg_quote(route('storefront.brands.index'), '/').'"[^>]+data-mega-menu-trigger="brands"/',
            $html,
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<button[^>]+data-mega-menu-trigger="brands"/',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/storefront-mobile-nav__row--split[\s\S]+href="'.preg_quote(route('storefront.shop.index'), '/').'"/',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/storefront-mobile-nav__row--split[\s\S]+href="'.preg_quote(route('storefront.brands.index'), '/').'"/',
            $html,
        );
        $this->assertStringContainsString('storefront-mega-menu__letters', $html);
        $this->assertStringContainsString(__('storefront::storefront.nav_browse_az'), $html);
        $this->assertStringContainsString(__('storefront::storefront.nav_explore'), $html);
        $this->assertStringContainsString(__('storefront::storefront.nav_view_all_brands'), $html);
        $this->assertStringContainsString('#brand-letter-h', $html);
        $this->assertStringNotContainsString('storefront-brand-card', $html);
    }

    public function test_brands_page_lists_active_brands(): void
    {
        $this->seedCatalog();

        $html = $this->get(route('storefront.brands.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Harbor Goods', $html);
        $this->assertStringContainsString(route('storefront.brands.show', 'harbor-goods'), $html);
        $this->assertStringNotContainsString(route('storefront.shop.index', ['brand' => 'harbor-goods']), $html);
    }

    private function seedCatalog(): void
    {
        $category = Category::query()->create([
            'name' => 'Home',
            'slug' => 'home-header-nav',
            'is_active' => true,
            'position' => 1,
        ]);
        $brand = Brand::query()->create([
            'name' => 'Harbor Goods',
            'slug' => 'harbor-goods',
            'is_active' => true,
        ]);
        $variant = $this->createPurchasableProduct(sku: 'HDR-SHOP-LINK-1');
        $variant->product->forceFill(['brand_uuid' => $brand->uuid])->save();
        $variant->product->categories()->attach($category->id);
    }
}
