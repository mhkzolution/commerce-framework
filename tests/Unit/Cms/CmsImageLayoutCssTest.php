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

        $this->assertStringContainsString('.cms-image-node', $editorCss);
        $this->assertStringContainsString('.cms-image-node[width$="%"]', $editorCss);
        $this->assertStringContainsString('.cms-image-node:not([data-align])', $editorCss);
        $this->assertStringContainsString('.cms-image-node img', $editorCss);
        $this->assertStringContainsString('.cms-editor-prose .cms-image-node img', $editorCss);

        preg_match(
            '/\.cms-image-node(?:\[|:|\s|\{|\})(.*?)(?=\.cms-image-node__handle)/s',
            $editorCss,
            $nodeBlockMatches,
        );
        $nodeBlock = $nodeBlockMatches[0] ?? '';
        $this->assertNotSame('', $nodeBlock, 'Editor CSS should define a .cms-image-node layout block');

        $this->assertStringContainsString('box-sizing: border-box', $nodeBlock);
        $this->assertStringContainsString('padding-inline: 0.25rem', $nodeBlock);
        $this->assertStringContainsString('max-width: 1023px', $nodeBlock);

        preg_match('/\.cms-editor-prose \.cms-image-node img\s*\{[^}]+\}/s', $editorCss, $nodeImgMatches);
        $this->assertNotEmpty($nodeImgMatches, 'Editor CSS should define inner NodeView img sizing');
        $this->assertStringContainsString('width: 100%', $nodeImgMatches[0]);
        $this->assertStringContainsString('margin: 0', $nodeImgMatches[0]);
        $this->assertStringContainsString('padding: 0', $nodeImgMatches[0]);
    }
}
