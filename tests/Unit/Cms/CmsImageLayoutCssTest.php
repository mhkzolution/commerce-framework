<?php

declare(strict_types=1);

namespace Tests\Unit\Cms;

use PHPUnit\Framework\TestCase;

final class CmsImageLayoutCssTest extends TestCase
{
    public function test_prose_css_honors_percent_width_gap_and_mobile_stack(): void
    {
        $blogCssPath = dirname(__DIR__, 3).'/resources/css/storefront/blog.css';
        $editorCssPath = dirname(__DIR__, 3).'/resources/css/admin/cms-editor.css';

        foreach ([$blogCssPath, $editorCssPath] as $path) {
            $css = file_get_contents($path);
            $this->assertNotFalse($css, $path);

            $this->assertStringContainsString('box-sizing: border-box', $css);
            $this->assertStringContainsString('padding-inline: 0.25rem', $css);
            $this->assertStringContainsString('img:not([width])', $css);
            $this->assertStringContainsString('max-width: 1023px', $css);
        }

        $blogCss = file_get_contents($blogCssPath);
        $this->assertNotFalse($blogCss);
        $this->assertStringNotContainsString(
            ".storefront-prose img {\n    display: block;\n    width: 100%;",
            $blogCss,
        );

        $editorCss = file_get_contents($editorCssPath);
        $this->assertNotFalse($editorCss);
        $this->assertStringContainsString('.cms-editor-prose img', $editorCss);
        $this->assertStringContainsString('inline-block', $editorCss);
        $this->assertTrue(
            str_contains($editorCss, 'attr(width)') || str_contains($editorCss, '[width$="%"]'),
            'Editor CSS should honor percent width via attr(width) or [width$="%"]',
        );
    }
}
