<?php

declare(strict_types=1);

namespace Tests\Unit\Storefront;

use PHPUnit\Framework\TestCase;

final class Ws002PdpIsolationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const FORBIDDEN_IN_PDP = [
        'defaultVariant',
        'CurrencyConverter',
        'cf-btn',
        'cf-input',
        'x-admin.',
        'x-storefront.layout.partials.site-header',
        'Setting::',
        '@auth',
        "config('commerce.name')",
        'feat/commerce-framework-v1',
        'packages/storefront-design-system',
        'lg:grid-cols-2',
    ];

    public function test_pdp_surfaces_exist(): void
    {
        $this->assertFileExists($this->bladePath());
        $this->assertFileExists($this->builderPath());
        $this->assertFileExists($this->dtoPath());
        $this->assertFileExists($this->cssPath());
    }

    public function test_pdp_uses_page_container_full_bleed_main_and_dto(): void
    {
        $blade = file_get_contents($this->bladePath());
        $this->assertNotFalse($blade);

        $this->assertStringContainsString('storefront-pdp-main', $blade);
        $this->assertStringContainsString('<x-storefront.layout.page-container', $blade);
        $this->assertStringContainsString('ProductDetailData', $blade);
        $this->assertStringContainsString('name="purchasable_uuid"', $blade);
        $this->assertStringContainsString('name="quantity"', $blade);
        $this->assertStringContainsString('storefront::storefront.add_to_cart', $blade);
        $this->assertStringContainsString("<img", $blade);
        $this->assertStringContainsString('@vite(\'resources/css/storefront/pdp.css\')', $blade);
    }

    public function test_pdp_blade_has_no_eloquent_admin_chrome_or_header_extract(): void
    {
        $blade = file_get_contents($this->bladePath());
        $this->assertNotFalse($blade);

        foreach (self::FORBIDDEN_IN_PDP as $token) {
            $this->assertStringNotContainsString($token, $blade, $token);
        }
    }

    public function test_controller_passes_detail_data_not_eloquent_or_converter(): void
    {
        $controller = file_get_contents($this->controllerPath());
        $this->assertNotFalse($controller);

        $this->assertStringContainsString('ProductDetailBuilder', $controller);

        if (! preg_match('/public function show\(string \$slug\): View\s*\{(?P<body>.*)\n    \}\n\}\n?\z/s', $controller, $match)) {
            $this->fail('ShopController::show not found');
        }

        $show = $match['body'];
        $this->assertStringContainsString('ProductDetailData', $show);
        $this->assertStringNotContainsString('defaultVariant', $show);
        $this->assertStringNotContainsString("'currencyConverter'", $show);
        $this->assertStringNotContainsString("'variant'", $show);
        $this->assertStringNotContainsString("'available'", $show);
    }

    public function test_dto_lives_in_storefront_contracts(): void
    {
        $contents = file_get_contents($this->dtoPath());
        $this->assertNotFalse($contents);
        $this->assertStringContainsString('namespace Commerce\\Contracts\\Storefront;', $contents);
        $this->assertStringContainsString('final readonly class ProductDetailData', $contents);
        foreach (['$name', '$description', '$imageUrl', '$price', '$displayCurrency', '$variantUuid', '$shopUrl'] as $field) {
            $this->assertStringContainsString($field, $contents);
        }
    }

    public function test_builder_is_cart_owned_and_uses_default_variant_off_the_blade(): void
    {
        $contents = file_get_contents($this->builderPath());
        $this->assertNotFalse($contents);
        $this->assertStringContainsString('namespace Commerce\\Cart\\Services;', $contents);
        $this->assertStringContainsString('ProductDetailData', $contents);
        $this->assertStringContainsString('MediaQueryServiceInterface', $contents);
        $this->assertStringContainsString('InventoryQueryServiceInterface', $contents);
    }

    public function test_pdp_css_uses_tokens_not_archive_width(): void
    {
        $css = file_get_contents($this->cssPath());
        $this->assertNotFalse($css);
        $this->assertStringContainsString('.storefront-pdp-main', $css);
        $this->assertStringContainsString('.storefront-pdp', $css);
        $this->assertStringContainsString('var(--store-gutter)', $css);
        $this->assertStringContainsString('var(--space-32)', $css);
        $this->assertStringNotContainsString('77.5rem', $css);
        $this->assertStringNotContainsString('87.5rem', $css);
    }

    public function test_vite_registers_pdp_css(): void
    {
        $vite = file_get_contents($this->repoRoot().'/vite.config.js');
        $this->assertNotFalse($vite);
        $this->assertStringContainsString('resources/css/storefront/pdp.css', $vite);
    }

    public function test_shop_blog_and_header_do_not_embed_pdp_chrome(): void
    {
        $shop = file_get_contents($this->repoRoot().'/modules/Cart/resources/views/storefront/shop.blade.php');
        $this->assertNotFalse($shop);
        $this->assertStringNotContainsString('storefront-pdp-main', $shop);
        $this->assertStringNotContainsString('ProductDetailData', $shop);

        $blog = file_get_contents($this->repoRoot().'/modules/Cms/resources/views/storefront/posts/index.blade.php');
        $this->assertNotFalse($blog);
        $this->assertStringNotContainsString('storefront-pdp-main', $blog);

        $header = file_get_contents(
            $this->repoRoot().'/resources/views/components/storefront/layout/partials/site-header.blade.php',
        );
        $this->assertNotFalse($header);
        $this->assertStringNotContainsString('ProductDetailData', $header);

        $layout = file_get_contents($this->repoRoot().'/modules/Cart/resources/views/layouts/storefront.blade.php');
        $this->assertNotFalse($layout);
        $this->assertStringContainsString('x-storefront.layout.partials.site-header', $layout);
        $this->assertStringContainsString('max-w-5xl', $layout);
    }

    public function test_product_card_primitive_is_unchanged(): void
    {
        $card = file_get_contents(
            $this->repoRoot().'/resources/views/components/storefront/cards/product.blade.php',
        );
        $this->assertNotFalse($card);
        $this->assertStringContainsString('ProductCardData', $card);
        $this->assertStringNotContainsString('ProductDetailData', $card);
        $this->assertStringNotContainsString('defaultVariant', $card);
    }

    private function bladePath(): string
    {
        return $this->repoRoot().'/modules/Cart/resources/views/storefront/product.blade.php';
    }

    private function builderPath(): string
    {
        return $this->repoRoot().'/modules/Cart/src/Services/ProductDetailBuilder.php';
    }

    private function dtoPath(): string
    {
        return $this->repoRoot().'/packages/commerce/contracts/src/Storefront/ProductDetailData.php';
    }

    private function cssPath(): string
    {
        return $this->repoRoot().'/resources/css/storefront/pdp.css';
    }

    private function controllerPath(): string
    {
        return $this->repoRoot().'/modules/Cart/src/Http/Controllers/ShopController.php';
    }

    private function repoRoot(): string
    {
        return dirname(__DIR__, 3);
    }
}
