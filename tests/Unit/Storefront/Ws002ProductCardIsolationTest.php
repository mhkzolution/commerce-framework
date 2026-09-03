<?php

declare(strict_types=1);

namespace Tests\Unit\Storefront;

use PHPUnit\Framework\TestCase;

final class Ws002ProductCardIsolationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const FORBIDDEN = [
        'SiteIdentityServiceInterface',
        'WebsiteSettingsService',
        'AppearanceController',
        'StorefrontNavigationConfig',
        'defaultVariant',
        'Commerce\\Product\\Models\\Product',
        'feat/commerce-framework-v1',
        'packages/storefront-design-system',
        '->inventory',
        '->images',
        '->media',
        '->variants',
    ];

    public function test_product_card_surfaces_exist(): void
    {
        $this->assertFileExists($this->dtoPath());
        $this->assertFileExists($this->bladePath());
        $this->assertFileExists($this->cssPath());
        $this->assertFileExists($this->mapperPath());
    }

    public function test_app_css_imports_product_card_sheet(): void
    {
        $contents = file_get_contents($this->repoRoot().'/resources/css/app.css');
        $this->assertNotFalse($contents);
        $this->assertStringContainsString("@import './storefront/product-card.css';", $contents);
    }

    public function test_dto_is_product_card_data_in_contracts(): void
    {
        $this->assertFileExists($this->dtoPath());
        $contents = file_get_contents($this->dtoPath());
        $this->assertNotFalse($contents);
        $this->assertStringContainsString('namespace Commerce\\Contracts\\Storefront;', $contents);
        $this->assertStringContainsString('final readonly class ProductCardData', $contents);
        foreach (['uuid', 'name', 'slug', 'url', 'variantUuid', 'price', 'compareAtPrice', 'imageUrl', 'available', 'inStock'] as $field) {
            $this->assertStringContainsString('$'.$field, $contents);
        }
    }

    public function test_primitive_and_css_have_no_eloquent_or_archive_types(): void
    {
        $hits = [];

        foreach ([$this->bladePath(), $this->cssPath(), $this->dtoPath()] as $path) {
            $contents = file_get_contents($path);
            $this->assertNotFalse($contents, $path);

            foreach (self::FORBIDDEN as $token) {
                if (str_contains($contents, $token)) {
                    $hits[] = $path.' contains '.$token;
                }
            }
        }

        $this->assertSame([], $hits, implode("\n", $hits));
    }

    public function test_primitive_uses_product_card_data_and_storefront_class(): void
    {
        $this->assertFileExists($this->bladePath());
        $contents = file_get_contents($this->bladePath());
        $this->assertNotFalse($contents);
        $this->assertStringContainsString('ProductCardData', $contents);
        $this->assertStringContainsString('storefront-product-card', $contents);
    }

    public function test_homepage_and_shop_consume_the_primitive(): void
    {
        $slides = file_get_contents(
            $this->repoRoot().'/modules/Cart/resources/views/storefront/partials/home-product-slides.blade.php',
        );
        $this->assertNotFalse($slides);
        $this->assertStringContainsString('<x-storefront.cards.product', $slides);

        $shop = file_get_contents($this->repoRoot().'/modules/Cart/resources/views/storefront/shop.blade.php');
        $this->assertNotFalse($shop);
        $this->assertStringContainsString('<x-storefront.cards.product', $shop);
        $this->assertStringNotContainsString('defaultVariant', $shop);
        $this->assertStringContainsString('x-storefront.layout.page-container', $shop);
    }

    public function test_shop_controller_maps_through_product_card_mapper(): void
    {
        $path = $this->repoRoot().'/modules/Cart/src/Http/Controllers/ShopController.php';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);
        $this->assertStringContainsString('ProductCardMapper', $contents);
    }

    public function test_homepage_product_query_returns_product_card_data(): void
    {
        $path = $this->repoRoot().'/modules/Cart/src/Services/HomepageProductQuery.php';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);
        $this->assertStringContainsString('ProductCardData', $contents);
        $this->assertStringContainsString('ProductCardMapper', $contents);
    }

    public function test_no_header_extract_this_milestone(): void
    {
        $this->assertFileDoesNotExist(
            $this->repoRoot().'/resources/views/components/storefront/layout/partials/site-header.blade.php',
        );
    }

    private function dtoPath(): string
    {
        return $this->repoRoot().'/packages/commerce/contracts/src/Storefront/ProductCardData.php';
    }

    private function bladePath(): string
    {
        return $this->repoRoot().'/resources/views/components/storefront/cards/product.blade.php';
    }

    private function cssPath(): string
    {
        return $this->repoRoot().'/resources/css/storefront/product-card.css';
    }

    private function mapperPath(): string
    {
        return $this->repoRoot().'/modules/Cart/src/Services/ProductCardMapper.php';
    }

    private function repoRoot(): string
    {
        return dirname(__DIR__, 3);
    }
}
