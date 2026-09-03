<?php

declare(strict_types=1);

namespace Tests\Unit\Storefront;

use PHPUnit\Framework\TestCase;

final class Ws002HeaderIsolationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const FORBIDDEN = [
        'Setting::',
        'Navigation::',
        'User::',
        '@auth',
        "config('commerce.name')",
        'SiteIdentityServiceInterface',
        'WebsiteSettingsService',
        'AppearanceController',
        'CustomerExperienceController',
        'StorefrontNavigationConfig',
        'feat/commerce-framework-v1',
        'packages/storefront-design-system',
        'defaultVariant',
    ];

    /**
     * @var list<string>
     */
    private const DTO_CLASSES = [
        'HeaderBrandData',
        'HeaderNavigationData',
        'HeaderActionData',
        'HeaderViewData',
    ];

    public function test_header_surfaces_exist(): void
    {
        $this->assertFileExists($this->bladePath());
        $this->assertFileExists($this->builderPath());

        foreach (self::DTO_CLASSES as $class) {
            $this->assertFileExists($this->dtoPath($class));
        }
    }

    public function test_layout_uses_site_header_primitive_not_max_w_5xl_inner(): void
    {
        $layout = file_get_contents($this->layoutPath());
        $this->assertNotFalse($layout);

        $this->assertStringContainsString('x-storefront.layout.partials.site-header', $layout);
        $this->assertStringNotContainsString('<header class="storefront-header">', $layout);
        $this->assertStringNotContainsString('flex max-w-5xl items-center justify-between', $layout);
    }

    public function test_header_blade_consumes_view_data_without_models_or_auth(): void
    {
        $this->assertFileExists($this->bladePath());
        $contents = file_get_contents($this->bladePath());
        $this->assertNotFalse($contents);

        $this->assertStringContainsString('HeaderViewData', $contents);
        $this->assertStringContainsString('storefront-site-header', $contents);
        $this->assertStringContainsString('x-storefront.layout.page-container', $contents);
        $this->assertStringContainsString('name="search"', $contents);

        foreach (self::FORBIDDEN as $token) {
            $this->assertStringNotContainsString($token, $contents, $token);
        }
    }

    public function test_dtos_live_in_storefront_contracts(): void
    {
        foreach (self::DTO_CLASSES as $class) {
            $contents = file_get_contents($this->dtoPath($class));
            $this->assertNotFalse($contents, $class);
            $this->assertStringContainsString('namespace Commerce\\Contracts\\Storefront;', $contents);
            $this->assertStringContainsString('final readonly class '.$class, $contents);
        }

        $nav = file_get_contents($this->dtoPath('HeaderNavigationData'));
        $this->assertNotFalse($nav);
        $this->assertStringContainsString('NavigationLinkData', $nav);

        $actions = file_get_contents($this->dtoPath('HeaderActionData'));
        $this->assertNotFalse($actions);
        foreach (['searchUrl', 'cartUrl', 'cartCount', 'authenticated', 'accountUrl', 'loginUrl', 'logoutUrl'] as $field) {
            $this->assertStringContainsString('$'.$field, $actions);
        }
    }

    public function test_builder_is_cart_owned(): void
    {
        $this->assertFileExists($this->builderPath());
        $contents = file_get_contents($this->builderPath());
        $this->assertNotFalse($contents);
        $this->assertStringContainsString('namespace Commerce\\Cart\\Services;', $contents);
        $this->assertStringContainsString('HeaderViewData', $contents);
        $this->assertStringContainsString('links(\'main\')', $contents);
    }

    public function test_header_css_uses_tokens_not_archive_width(): void
    {
        $css = $this->headerCss();
        $this->assertNotSame('', $css);
        $this->assertStringContainsString('storefront-site-header', $css);
        $this->assertStringNotContainsString('77.5rem', $css);
        $this->assertStringNotContainsString('feat/commerce-framework-v1', $css);
    }

    public function test_shop_view_does_not_embed_site_header(): void
    {
        $shop = file_get_contents($this->repoRoot().'/modules/Cart/resources/views/storefront/shop.blade.php');
        $this->assertNotFalse($shop);
        $this->assertStringNotContainsString('x-storefront.layout.partials.site-header', $shop);
    }

    public function test_no_storefront_design_system_package(): void
    {
        $this->assertDirectoryDoesNotExist($this->repoRoot().'/packages/storefront-design-system');
        $this->assertDirectoryDoesNotExist($this->repoRoot().'/modules/Storefront');
    }

    private function headerCss(): string
    {
        $headerCss = $this->repoRoot().'/resources/css/storefront/header.css';
        if (is_file($headerCss)) {
            $contents = file_get_contents($headerCss);

            return $contents === false ? '' : $contents;
        }

        $shell = file_get_contents($this->repoRoot().'/resources/css/storefront/shell.css');

        return $shell === false ? '' : $shell;
    }

    private function bladePath(): string
    {
        return $this->repoRoot().'/resources/views/components/storefront/layout/partials/site-header.blade.php';
    }

    private function layoutPath(): string
    {
        return $this->repoRoot().'/modules/Cart/resources/views/layouts/storefront.blade.php';
    }

    private function builderPath(): string
    {
        return $this->repoRoot().'/modules/Cart/src/Services/HeaderViewModelBuilder.php';
    }

    private function dtoPath(string $class): string
    {
        return $this->repoRoot().'/packages/commerce/contracts/src/Storefront/'.$class.'.php';
    }

    private function repoRoot(): string
    {
        return dirname(__DIR__, 3);
    }
}
