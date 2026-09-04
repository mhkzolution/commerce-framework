<?php

declare(strict_types=1);

namespace Tests\Unit\Storefront;

use PHPUnit\Framework\TestCase;

final class Ws002IsolationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const FORBIDDEN = [
        'SiteIdentityServiceInterface',
        'SiteIdentityService',
        'WebsiteSettingsService',
        'AppearanceController',
        'CustomerExperienceController',
        'CustomerExperienceConfig',
        'StorefrontNavigationConfig',
        'feat/commerce-framework-v1',
        'modules/Footer',
        'packages/storefront-design-system',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_TOKEN_VARS = [
        '--store-max-width: 80rem',
        '--store-max-width-narrow: 56.25rem',
        '--store-gutter: 1.5rem',
        '--radius-store: 1.5rem',
        '--radius-store-lg: 1.75rem',
        '--radius-xl: 1rem',
        '--space-4:',
        '--space-8:',
        '--space-12:',
        '--space-16:',
        '--space-24:',
        '--space-32:',
        '--space-48:',
    ];

    /**
     * @var list<string>
     */
    private const HOMEPAGE_INNER_VIEWS = [
        'modules/Cart/resources/views/storefront/partials/home-section-arrivals.blade.php',
        'modules/Cart/resources/views/storefront/partials/home-section-articles.blade.php',
        'modules/Cart/resources/views/storefront/partials/home-section-categories.blade.php',
        'modules/Cart/resources/views/storefront/partials/home-section-faq.blade.php',
        'modules/Cart/resources/views/storefront/partials/home-section-promotions.blade.php',
    ];

    public function test_m1_surfaces_exist(): void
    {
        $this->assertFileExists($this->tokensPath());
        $this->assertFileExists($this->pageContainerPath());
    }

    public function test_app_css_imports_storefront_tokens_before_other_storefront_sheets(): void
    {
        $path = $this->repoRoot().'/resources/css/app.css';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);

        $this->assertStringContainsString("@import './storefront/tokens.css';", $contents);

        $tokensAt = strpos($contents, "@import './storefront/tokens.css';");
        $shellAt = strpos($contents, "@import './storefront/shell.css';");
        $this->assertNotFalse($tokensAt);
        $this->assertNotFalse($shellAt);
        $this->assertLessThan($shellAt, $tokensAt);
    }

    public function test_tokens_define_locked_storefront_variables(): void
    {
        $this->assertFileExists($this->tokensPath());

        $contents = file_get_contents($this->tokensPath());
        $this->assertNotFalse($contents);

        foreach (self::REQUIRED_TOKEN_VARS as $token) {
            $this->assertStringContainsString($token, $contents);
        }

        $this->assertStringContainsString('.storefront', $contents);
        $this->assertStringContainsString('.storefront-page-container', $contents);
        $this->assertStringContainsString('.storefront-page-container--narrow', $contents);
    }

    public function test_m1_files_have_no_forbidden_archive_types(): void
    {
        $hits = [];

        foreach ($this->m1Files() as $path) {
            $this->assertFileExists($path);
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

    public function test_storefront_tokens_are_not_defined_in_admin_semantic_light(): void
    {
        $path = $this->repoRoot().'/resources/css/tokens/semantic-light.css';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);

        $this->assertStringNotContainsString('--store-max-width', $contents);
        $this->assertStringNotContainsString('--store-gutter', $contents);
        $this->assertStringNotContainsString('--radius-store', $contents);
        $this->assertStringNotContainsString('--space-4:', $contents);
    }

    public function test_tokens_do_not_dump_archive_footer_or_header_css(): void
    {
        $this->assertFileExists($this->tokensPath());

        $contents = file_get_contents($this->tokensPath());
        $this->assertNotFalse($contents);

        $this->assertStringNotContainsString('.storefront-site-footer', $contents);
        $this->assertStringNotContainsString('.storefront-header', $contents);
        $this->assertStringNotContainsString('Prompt', $contents);
    }

    public function test_no_storefront_design_system_package_or_module(): void
    {
        $this->assertDirectoryDoesNotExist($this->repoRoot().'/packages/storefront-design-system');
        $this->assertDirectoryDoesNotExist($this->repoRoot().'/modules/Storefront');
        $this->assertDirectoryDoesNotExist($this->repoRoot().'/modules/Footer');
    }

    public function test_m1_does_not_extract_site_header_or_redesign_navigation(): void
    {
        $layout = file_get_contents($this->repoRoot().'/modules/Cart/resources/views/layouts/storefront.blade.php');
        $this->assertNotFalse($layout);
        $this->assertStringContainsString('x-storefront.layout.partials.site-header', $layout);
        $this->assertStringNotContainsString('NavigationQueryServiceInterface', $layout);
        $this->assertStringNotContainsString('links(\'main\')', $layout);

        $tokens = file_get_contents($this->tokensPath());
        $this->assertNotFalse($tokens);
        $this->assertStringNotContainsString('.storefront-site-header', $tokens);

        $pageContainer = file_get_contents($this->pageContainerPath());
        $this->assertNotFalse($pageContainer);
        $this->assertStringNotContainsString('storefront-site-header', $pageContainer);
    }

    public function test_m1_does_not_add_archive_product_card_or_site_header_on_shop(): void
    {
        $this->assertFileDoesNotExist(
            $this->repoRoot().'/resources/views/components/storefront/cards/product-card.blade.php',
        );

        $shop = file_get_contents($this->repoRoot().'/modules/Cart/resources/views/storefront/shop.blade.php');
        $this->assertNotFalse($shop);
        $this->assertStringContainsString('x-storefront.cards.product', $shop);
        $this->assertStringContainsString('x-storefront.layout.page-container', $shop);
        $this->assertStringNotContainsString('x-storefront.layout.partials.site-header', $shop);
    }

    public function test_m1_does_not_rewrite_site_footer(): void
    {
        $footer = file_get_contents(
            $this->repoRoot().'/resources/views/components/storefront/layout/partials/site-footer.blade.php',
        );
        $this->assertNotFalse($footer);
        $this->assertStringContainsString('FooterPageData', $footer);
        $this->assertStringNotContainsString('x-storefront.layout.page-container', $footer);

        $css = file_get_contents($this->repoRoot().'/resources/css/storefront/footer.css');
        $this->assertNotFalse($css);
        $this->assertStringContainsString('var(--store-max-width)', $css);
        $this->assertStringNotContainsString('77.5rem', $css);
    }

    public function test_homepage_inners_use_page_container(): void
    {
        foreach (self::HOMEPAGE_INNER_VIEWS as $relative) {
            $path = $this->repoRoot().'/'.$relative;
            $contents = file_get_contents($path);
            $this->assertNotFalse($contents, $path);
            $this->assertStringContainsString(
                '<x-storefront.layout.page-container',
                $contents,
                $relative.' must use x-storefront.layout.page-container',
            );
            $this->assertStringNotContainsString(
                '<div class="storefront-home__inner',
                $contents,
                $relative.' must not keep a raw storefront-home__inner wrapper',
            );
        }
    }

    public function test_homepage_faq_uses_narrow_page_container(): void
    {
        $path = $this->repoRoot().'/modules/Cart/resources/views/storefront/partials/home-section-faq.blade.php';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);
        $this->assertStringContainsString('variant="narrow"', $contents);
    }

    public function test_homepage_hero_stays_full_bleed(): void
    {
        $path = $this->repoRoot().'/modules/Cart/resources/views/storefront/partials/home-section-hero.blade.php';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);
        $this->assertStringNotContainsString('x-storefront.layout.page-container', $contents);
    }

    public function test_homepage_css_does_not_hardcode_container_width(): void
    {
        $path = $this->repoRoot().'/resources/css/storefront/home.css';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);

        $this->assertStringNotContainsString('77.5rem', $contents);
        $this->assertStringNotContainsString('max-width: 56.25rem', $contents);
        $this->assertStringContainsString('var(--radius-store)', $contents);
        $this->assertStringContainsString('var(--radius-store-lg)', $contents);
    }

    public function test_page_container_is_width_and_gutter_only(): void
    {
        $this->assertFileExists($this->pageContainerPath());

        $contents = file_get_contents($this->pageContainerPath());
        $this->assertNotFalse($contents);

        foreach (self::FORBIDDEN as $token) {
            $this->assertStringNotContainsString($token, $contents);
        }

        $this->assertStringNotContainsString('FooterPageData', $contents);
        $this->assertStringNotContainsString('HomepageProductCardData', $contents);
        $this->assertStringContainsString('$slot', $contents);
        $this->assertStringContainsString('storefront-page-container', $contents);
    }

    /**
     * @return list<string>
     */
    private function m1Files(): array
    {
        return [
            $this->tokensPath(),
            $this->pageContainerPath(),
            $this->repoRoot().'/resources/css/app.css',
            $this->repoRoot().'/resources/css/storefront/home.css',
            $this->repoRoot().'/modules/Cart/resources/views/storefront/partials/home-section-arrivals.blade.php',
            $this->repoRoot().'/modules/Cart/resources/views/storefront/partials/home-section-articles.blade.php',
            $this->repoRoot().'/modules/Cart/resources/views/storefront/partials/home-section-categories.blade.php',
            $this->repoRoot().'/modules/Cart/resources/views/storefront/partials/home-section-faq.blade.php',
            $this->repoRoot().'/modules/Cart/resources/views/storefront/partials/home-section-promotions.blade.php',
        ];
    }

    private function tokensPath(): string
    {
        return $this->repoRoot().'/resources/css/storefront/tokens.css';
    }

    private function pageContainerPath(): string
    {
        return $this->repoRoot().'/resources/views/components/storefront/layout/page-container.blade.php';
    }

    private function repoRoot(): string
    {
        return dirname(__DIR__, 3);
    }
}
