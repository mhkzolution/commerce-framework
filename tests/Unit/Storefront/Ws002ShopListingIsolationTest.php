<?php

declare(strict_types=1);

namespace Tests\Unit\Storefront;

use PHPUnit\Framework\TestCase;

final class Ws002ShopListingIsolationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const FORBIDDEN = [
        'SiteIdentityServiceInterface',
        'WebsiteSettingsService',
        'AppearanceController',
        'StorefrontNavigationConfig',
        'feat/commerce-framework-v1',
        'packages/storefront-design-system',
        'x-storefront.layout.partials.site-header',
        'x-admin.search-input',
        'defaultVariant',
    ];

    public function test_shop_listing_surfaces_exist(): void
    {
        $this->assertFileExists($this->shopCssPath());
        $this->assertFileExists($this->toolbarPath());
        $this->assertFileExists($this->paginationPath());
        $this->assertFileExists($this->queryPath());
    }

    public function test_shop_uses_page_container_toolbar_empty_state_and_storefront_pagination(): void
    {
        $shop = file_get_contents($this->shopViewPath());
        $this->assertNotFalse($shop);

        $this->assertStringContainsString('storefront-shop-main', $shop);
        $this->assertStringContainsString('<x-storefront.layout.page-container', $shop);
        $this->assertStringContainsString('<x-storefront.shop.toolbar', $shop);
        $this->assertStringContainsString('<x-storefront.empty-state', $shop);
        $this->assertStringContainsString("links('pagination::storefront')", $shop);
        $this->assertStringContainsString('<x-storefront.cards.product', $shop);
    }

    public function test_shop_view_has_no_admin_search_eloquent_or_header_extract(): void
    {
        $shop = file_get_contents($this->shopViewPath());
        $this->assertNotFalse($shop);

        foreach (self::FORBIDDEN as $token) {
            $this->assertStringNotContainsString($token, $shop, $token);
        }
    }

    public function test_shop_toolbar_has_count_sort_and_view_mode_seam(): void
    {
        $this->assertFileExists($this->toolbarPath());
        $contents = file_get_contents($this->toolbarPath());
        $this->assertNotFalse($contents);
        $this->assertStringContainsString('storefront-shop-toolbar', $contents);
        $this->assertStringContainsString('x-storefront.forms.sort-dropdown', $contents);
        $this->assertStringContainsString('storefront-shop-toolbar__view', $contents);
        $this->assertStringNotContainsString('x-admin.search-input', $contents);
    }

    public function test_vite_config_includes_shop_css(): void
    {
        $contents = file_get_contents($this->repoRoot().'/vite.config.js');
        $this->assertNotFalse($contents);
        $this->assertStringContainsString('resources/css/storefront/shop.css', $contents);
    }

    public function test_shop_css_uses_storefront_tokens_not_archive_width(): void
    {
        $this->assertFileExists($this->shopCssPath());
        $contents = file_get_contents($this->shopCssPath());
        $this->assertNotFalse($contents);
        $this->assertStringContainsString('.storefront-shop', $contents);
        $this->assertStringContainsString('.storefront-pagination', $contents);
        $this->assertStringContainsString('var(--font-store)', $contents);
        $this->assertStringNotContainsString('77.5rem', $contents);
        $this->assertStringNotContainsString('x-storefront.layout.partials.site-header', $contents);
    }

    public function test_shop_controller_uses_shop_product_query(): void
    {
        $path = $this->repoRoot().'/modules/Cart/src/Http/Controllers/ShopController.php';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);
        $this->assertStringContainsString('ShopProductQuery', $contents);
        $this->assertStringContainsString('ProductCardMapper', $contents);
    }

    public function test_header_is_not_extracted(): void
    {
        $this->assertFileDoesNotExist(
            $this->repoRoot().'/resources/views/components/storefront/layout/partials/site-header.blade.php',
        );

        $layout = file_get_contents($this->repoRoot().'/modules/Cart/resources/views/layouts/storefront.blade.php');
        $this->assertNotFalse($layout);
        $this->assertStringContainsString('<header class="storefront-header">', $layout);
        $this->assertStringContainsString('max-w-5xl', $layout);
    }

    public function test_product_card_primitive_is_not_rewritten(): void
    {
        $card = file_get_contents(
            $this->repoRoot().'/resources/views/components/storefront/cards/product.blade.php',
        );
        $this->assertNotFalse($card);
        $this->assertStringContainsString('ProductCardData', $card);
        $this->assertStringNotContainsString('defaultVariant', $card);
    }

    private function shopViewPath(): string
    {
        return $this->repoRoot().'/modules/Cart/resources/views/storefront/shop.blade.php';
    }

    private function shopCssPath(): string
    {
        return $this->repoRoot().'/resources/css/storefront/shop.css';
    }

    private function toolbarPath(): string
    {
        return $this->repoRoot().'/resources/views/components/storefront/shop/toolbar.blade.php';
    }

    private function paginationPath(): string
    {
        return $this->repoRoot().'/resources/views/vendor/pagination/storefront.blade.php';
    }

    private function queryPath(): string
    {
        return $this->repoRoot().'/modules/Cart/src/Services/ShopProductQuery.php';
    }

    private function repoRoot(): string
    {
        return dirname(__DIR__, 3);
    }
}
