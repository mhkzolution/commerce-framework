<?php

declare(strict_types=1);

namespace Tests\Unit\Storefront;

use PHPUnit\Framework\TestCase;

final class Ws002BlogIsolationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const FORBIDDEN_IN_BLOG_VIEWS = [
        'x-admin.search-input',
        'x-storefront.layout.partials.site-header',
        'Setting::',
        '@auth',
        "config('commerce.name')",
        "vite('resources/js/storefront/blog.js')",
        'feat/commerce-framework-v1',
        'packages/storefront-design-system',
        'AppearanceController',
        'CustomerExperienceController',
    ];

    public function test_archive_uses_page_container_storefront_pagination_and_full_bleed_main(): void
    {
        $index = file_get_contents($this->indexPath());
        $this->assertNotFalse($index);

        $this->assertStringContainsString("storefront-blog-main", $index);
        $this->assertStringContainsString('<x-storefront.layout.page-container', $index);
        $this->assertStringContainsString("links('pagination::storefront')", $index);
        $this->assertStringContainsString('<x-storefront.blog.toolbar', $index);
        $this->assertStringContainsString('<x-storefront.empty-state', $index);
        $this->assertStringNotContainsString('storefront-blog-shell', $index);
        $this->assertStringNotContainsString('->links()', $index);
    }

    public function test_article_uses_page_container_with_narrow_reading_column(): void
    {
        $show = file_get_contents($this->showPath());
        $this->assertNotFalse($show);

        $this->assertStringContainsString("storefront-blog-main", $show);
        $this->assertStringContainsString('<x-storefront.layout.page-container', $show);
        $this->assertStringContainsString('variant="narrow"', $show);
        $this->assertStringNotContainsString('storefront-blog-shell', $show);
    }

    public function test_blog_views_drop_admin_search_header_extract_and_second_vite(): void
    {
        foreach ([$this->indexPath(), $this->showPath(), $this->toolbarPath()] as $path) {
            $contents = file_get_contents($path);
            $this->assertNotFalse($contents, $path);

            foreach (self::FORBIDDEN_IN_BLOG_VIEWS as $token) {
                $this->assertStringNotContainsString($token, $contents, $path.' contains '.$token);
            }
        }
    }

    public function test_toolbar_keeps_get_search_without_admin_input(): void
    {
        $toolbar = file_get_contents($this->toolbarPath());
        $this->assertNotFalse($toolbar);

        $this->assertStringContainsString('method="GET"', $toolbar);
        $this->assertStringContainsString('name="search"', $toolbar);
        $this->assertStringContainsString('data-blog-search-form', $toolbar);
        $this->assertStringContainsString('data-blog-search-input', $toolbar);
        $this->assertStringContainsString('x-storefront.forms.sort-dropdown', $toolbar);
        $this->assertStringNotContainsString('x-admin.search-input', $toolbar);
        $this->assertStringNotContainsString('x-admin.', $toolbar);
    }

    public function test_blog_css_drops_archive_width_and_parallel_page_container_clones(): void
    {
        $css = file_get_contents($this->blogCssPath());
        $this->assertNotFalse($css);

        $this->assertStringContainsString('.storefront-blog-main', $css);
        $this->assertStringContainsString('var(--store-gutter)', $css);
        $this->assertStringContainsString('var(--store-max-width-narrow)', $css);
        $this->assertStringContainsString('.storefront-pagination', $css);
        $this->assertStringNotContainsString('87.5rem', $css);
        $this->assertStringNotContainsString('.storefront-page-container--wide', $css);
        $this->assertStringNotContainsString('.storefront-page-container--reading', $css);
        $this->assertStringNotContainsString('max-width: 87.5rem', $css);
    }

    public function test_blog_js_loads_from_app_entry_not_a_second_vite_page(): void
    {
        $appJs = file_get_contents($this->repoRoot().'/resources/js/app.js');
        $this->assertNotFalse($appJs);
        $this->assertStringContainsString("import './storefront/blog.js'", $appJs);

        $vite = file_get_contents($this->repoRoot().'/vite.config.js');
        $this->assertNotFalse($vite);
        $this->assertStringNotContainsString("'resources/js/storefront/blog.js'", $vite);
    }

    public function test_shop_homepage_and_header_do_not_embed_blog_chrome(): void
    {
        $shop = file_get_contents($this->repoRoot().'/modules/Cart/resources/views/storefront/shop.blade.php');
        $this->assertNotFalse($shop);
        $this->assertStringNotContainsString('x-storefront.blog.', $shop);
        $this->assertStringNotContainsString('storefront-blog-main', $shop);

        $header = file_get_contents(
            $this->repoRoot().'/resources/views/components/storefront/layout/partials/site-header.blade.php',
        );
        $this->assertNotFalse($header);
        $this->assertStringNotContainsString('x-storefront.blog.', $header);

        $layout = file_get_contents($this->repoRoot().'/modules/Cart/resources/views/layouts/storefront.blade.php');
        $this->assertNotFalse($layout);
        $this->assertStringContainsString('x-storefront.layout.partials.site-header', $layout);
        $this->assertStringContainsString("max-w-5xl", $layout);
    }

    private function indexPath(): string
    {
        return $this->repoRoot().'/modules/Cms/resources/views/storefront/posts/index.blade.php';
    }

    private function showPath(): string
    {
        return $this->repoRoot().'/modules/Cms/resources/views/storefront/posts/show.blade.php';
    }

    private function toolbarPath(): string
    {
        return $this->repoRoot().'/resources/views/components/storefront/blog/toolbar.blade.php';
    }

    private function blogCssPath(): string
    {
        return $this->repoRoot().'/resources/css/storefront/blog.css';
    }

    private function repoRoot(): string
    {
        return dirname(__DIR__, 3);
    }
}
