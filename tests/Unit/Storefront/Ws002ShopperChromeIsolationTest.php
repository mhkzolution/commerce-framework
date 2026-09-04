<?php

declare(strict_types=1);

namespace Tests\Unit\Storefront;

use PHPUnit\Framework\TestCase;

final class Ws002ShopperChromeIsolationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const VIEWS = [
        'modules/Cart/resources/views/storefront/cart.blade.php',
        'modules/Cart/resources/views/storefront/checkout.blade.php',
        'modules/Cart/resources/views/storefront/_checkout_address_fields.blade.php',
        'modules/Cart/resources/views/storefront/confirmation.blade.php',
        'modules/Payment/resources/views/storefront/pay.blade.php',
        'modules/Customers/resources/views/storefront/account.blade.php',
        'modules/Customers/resources/views/storefront/order.blade.php',
        'modules/Customers/resources/views/storefront/login.blade.php',
        'modules/Customers/resources/views/storefront/register.blade.php',
        'modules/Customers/resources/views/storefront/_address_form.blade.php',
    ];

    /**
     * @var list<string>
     */
    private const PAGE_VIEWS = [
        'modules/Cart/resources/views/storefront/cart.blade.php',
        'modules/Cart/resources/views/storefront/checkout.blade.php',
        'modules/Cart/resources/views/storefront/confirmation.blade.php',
        'modules/Payment/resources/views/storefront/pay.blade.php',
        'modules/Customers/resources/views/storefront/account.blade.php',
        'modules/Customers/resources/views/storefront/order.blade.php',
        'modules/Customers/resources/views/storefront/login.blade.php',
        'modules/Customers/resources/views/storefront/register.blade.php',
    ];

    /**
     * @var list<string>
     */
    private const FORBIDDEN = [
        'cf-btn',
        'cf-input',
        'cf-flash',
        'cf-badge',
        'x-admin.',
        'customers::admin._address_form',
        'lg:grid-cols-2',
        'x-storefront.layout.partials.site-header',
        'Setting::',
        '@auth',
        'feat/commerce-framework-v1',
        'packages/storefront-design-system',
        'AppearanceController',
        'CustomerExperienceController',
    ];

    public function test_shopper_css_exists_and_is_registered(): void
    {
        $this->assertFileExists($this->repoRoot().'/resources/css/storefront/shopper.css');

        $vite = file_get_contents($this->repoRoot().'/vite.config.js');
        $this->assertNotFalse($vite);
        $this->assertStringContainsString('resources/css/storefront/shopper.css', $vite);
    }

    public function test_page_views_use_shopper_main_page_container_and_shared_css(): void
    {
        foreach (self::PAGE_VIEWS as $relative) {
            $blade = file_get_contents($this->repoRoot().'/'.$relative);
            $this->assertNotFalse($blade, $relative);
            $this->assertStringContainsString('storefront-shopper-main', $blade, $relative);
            $this->assertStringContainsString('<x-storefront.layout.page-container', $blade, $relative);
            $this->assertStringContainsString("@vite('resources/css/storefront/shopper.css')", $blade, $relative);
            $this->assertStringContainsString('storefront::', $blade, $relative);
        }
    }

    public function test_cart_uses_empty_state_and_keeps_cart_data(): void
    {
        $blade = file_get_contents($this->repoRoot().'/modules/Cart/resources/views/storefront/cart.blade.php');
        $this->assertNotFalse($blade);
        $this->assertStringContainsString('x-storefront.empty-state', $blade);
        $this->assertStringContainsString('$cart->lines', $blade);
        $this->assertStringNotContainsString('Your cart is empty', $blade);
        $this->assertStringNotContainsString('Clear cart', $blade);
    }

    public function test_listed_views_have_no_admin_chrome_or_archive_extract(): void
    {
        foreach (self::VIEWS as $relative) {
            $path = $this->repoRoot().'/'.$relative;
            $this->assertFileExists($path, $relative);
            $blade = file_get_contents($path);
            $this->assertNotFalse($blade, $relative);

            foreach (self::FORBIDDEN as $token) {
                $this->assertStringNotContainsString($token, $blade, $relative.' contains '.$token);
            }
        }
    }

    public function test_shopper_css_uses_tokens_not_archive_width(): void
    {
        $css = file_get_contents($this->repoRoot().'/resources/css/storefront/shopper.css');
        $this->assertNotFalse($css);
        $this->assertStringContainsString('.storefront-shopper-main', $css);
        $this->assertStringContainsString('.storefront-btn', $css);
        $this->assertStringContainsString('.storefront-input', $css);
        $this->assertStringContainsString('var(--store-gutter)', $css);
        $this->assertStringContainsString('var(--space-32)', $css);
        $this->assertStringNotContainsString('77.5rem', $css);
        $this->assertStringNotContainsString('87.5rem', $css);
    }

    public function test_shop_blog_pdp_and_header_do_not_embed_shopper_main(): void
    {
        $shop = file_get_contents($this->repoRoot().'/modules/Cart/resources/views/storefront/shop.blade.php');
        $this->assertNotFalse($shop);
        $this->assertStringNotContainsString('storefront-shopper-main', $shop);

        $blog = file_get_contents($this->repoRoot().'/modules/Cms/resources/views/storefront/posts/index.blade.php');
        $this->assertNotFalse($blog);
        $this->assertStringNotContainsString('storefront-shopper-main', $blog);

        $pdp = file_get_contents($this->repoRoot().'/modules/Cart/resources/views/storefront/product.blade.php');
        $this->assertNotFalse($pdp);
        $this->assertStringNotContainsString('storefront-shopper-main', $pdp);

        $header = file_get_contents(
            $this->repoRoot().'/resources/views/components/storefront/layout/partials/site-header.blade.php',
        );
        $this->assertNotFalse($header);
        $this->assertStringNotContainsString('storefront-shopper-main', $header);

        $layout = file_get_contents($this->repoRoot().'/modules/Cart/resources/views/layouts/storefront.blade.php');
        $this->assertNotFalse($layout);
        $this->assertStringContainsString('x-storefront.layout.partials.site-header', $layout);
        $this->assertStringContainsString('max-w-5xl', $layout);
    }

    private function repoRoot(): string
    {
        return dirname(__DIR__, 3);
    }
}
