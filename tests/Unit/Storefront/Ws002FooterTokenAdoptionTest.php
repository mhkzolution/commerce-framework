<?php

declare(strict_types=1);

namespace Tests\Unit\Storefront;

use PHPUnit\Framework\TestCase;

final class Ws002FooterTokenAdoptionTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const FORBIDDEN = [
        'SiteIdentityServiceInterface',
        'WebsiteSettingsService',
        'AppearanceController',
        'CustomerExperienceController',
        'StorefrontNavigationConfig',
        'feat/commerce-framework-v1',
        'packages/storefront-design-system',
    ];

    public function test_footer_css_uses_locked_spacing_radius_and_type_tokens(): void
    {
        $css = $this->footerCss();

        $this->assertStringContainsString('max-width: var(--store-max-width)', $css);
        $this->assertStringContainsString('padding-inline: var(--store-gutter)', $css);
        $this->assertStringContainsString('padding-block: var(--space-32)', $css);
        $this->assertStringContainsString('gap: var(--space-24)', $css);
        $this->assertStringContainsString('gap: var(--space-12)', $css);
        $this->assertStringContainsString('var(--space-8)', $css);
        $this->assertStringContainsString('var(--space-16)', $css);
        $this->assertStringContainsString('var(--space-48)', $css);
        $this->assertStringContainsString('border-radius: var(--radius-sm)', $css);
        $this->assertStringContainsString('font-family: var(--font-store)', $css);
        $this->assertStringContainsString('var(--color-muted)', $css);
    }

    public function test_footer_css_drops_on_scale_hardcoded_spacing_and_false_muted_token(): void
    {
        $css = $this->footerCss();

        $this->assertStringNotContainsString('padding-block: 1rem', $css);
        $this->assertStringNotContainsString('padding-block: 1.5rem', $css);
        $this->assertStringNotContainsString('padding-block: 2rem', $css);
        $this->assertStringNotContainsString('padding-block: 3rem', $css);
        $this->assertStringNotContainsString('gap: 1.5rem', $css);
        $this->assertStringNotContainsString('gap: 2rem', $css);
        $this->assertStringNotContainsString('--color-text-muted', $css);
        $this->assertStringNotContainsString('border-radius: 0.25rem', $css);
    }

    public function test_footer_css_keeps_off_scale_locals(): void
    {
        $css = $this->footerCss();

        $this->assertStringContainsString('0.625rem', $css);
        $this->assertStringContainsString('1.25rem', $css);
        $this->assertStringContainsString('2.5rem', $css);
        $this->assertStringContainsString('10rem', $css);
    }

    public function test_footer_blade_is_not_rewritten(): void
    {
        $blade = file_get_contents($this->bladePath());
        $this->assertNotFalse($blade);

        $this->assertStringContainsString('FooterPageData', $blade);
        $this->assertStringNotContainsString('x-storefront.layout.page-container', $blade);

        foreach (self::FORBIDDEN as $token) {
            $this->assertStringNotContainsString($token, $blade);
        }
    }

    public function test_footer_css_has_no_forbidden_archive_types(): void
    {
        $css = $this->footerCss();

        foreach (self::FORBIDDEN as $token) {
            $this->assertStringNotContainsString($token, $css);
        }
    }

    public function test_phase_2_does_not_extract_header_or_touch_shop(): void
    {
        $this->assertFileDoesNotExist(
            $this->repoRoot().'/resources/views/components/storefront/layout/partials/site-header.blade.php',
        );

        $layout = file_get_contents($this->repoRoot().'/modules/Cart/resources/views/layouts/storefront.blade.php');
        $this->assertNotFalse($layout);
        $this->assertStringContainsString('<header class="storefront-header">', $layout);

        $shop = file_get_contents($this->repoRoot().'/modules/Cart/resources/views/storefront/shop.blade.php');
        $this->assertNotFalse($shop);
        $this->assertStringNotContainsString('x-storefront.layout.page-container', $shop);
    }

    public function test_tokens_file_scale_is_not_expanded(): void
    {
        $tokens = file_get_contents($this->repoRoot().'/resources/css/storefront/tokens.css');
        $this->assertNotFalse($tokens);

        $this->assertStringNotContainsString('--space-40', $tokens);
        $this->assertStringNotContainsString('--space-10', $tokens);
        $this->assertStringContainsString('--space-24: 1.5rem', $tokens);
        $this->assertStringContainsString('--font-store', $tokens);
    }

    private function footerCss(): string
    {
        $path = $this->repoRoot().'/resources/css/storefront/footer.css';
        $this->assertFileExists($path);
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);

        return $contents;
    }

    private function bladePath(): string
    {
        return $this->repoRoot().'/resources/views/components/storefront/layout/partials/site-footer.blade.php';
    }

    private function repoRoot(): string
    {
        return dirname(__DIR__, 3);
    }
}
