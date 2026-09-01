<?php

declare(strict_types=1);

namespace Tests\Unit\Cms;

use Commerce\Cms\Services\EditorPipeline;
use PHPUnit\Framework\TestCase;

final class EditorPipelineTest extends TestCase
{
    private EditorPipeline $pipeline;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pipeline = new EditorPipeline;
    }

    public function test_it_strips_script_and_event_handlers(): void
    {
        $html = '<p onclick="alert(1)">Hello</p><script>alert(1)</script>';

        $this->assertSame('<p>Hello</p>', $this->pipeline->sanitize($html));
    }

    public function test_it_keeps_editor_v1_markup(): void
    {
        $html = '<h2>Title</h2><p><strong>Bold</strong> and <em>italic</em></p><ul><li>One</li></ul><blockquote>Quote</blockquote><pre><code>code()</code></pre><table><tr><th>A</th></tr><tr><td>B</td></tr></table>';

        $this->assertSame($html, $this->pipeline->sanitize($html));
    }

    public function test_it_neutralizes_javascript_links_and_keeps_images(): void
    {
        $html = '<p><a href="javascript:alert(1)">x</a><img src="/media/hero.jpg" alt="Hero" onerror="alert(1)"></p>';

        $sanitized = $this->pipeline->sanitize($html);

        $this->assertStringContainsString('href="#"', $sanitized);
        $this->assertStringContainsString('src="/media/hero.jpg"', $sanitized);
        $this->assertStringContainsString('alt="Hero"', $sanitized);
        $this->assertStringNotContainsString('onerror', $sanitized);
        $this->assertStringNotContainsString('javascript:', $sanitized);
    }

    public function test_empty_content_is_preserved(): void
    {
        $this->assertNull($this->pipeline->sanitize(null));
        $this->assertSame('', $this->pipeline->sanitize(''));
    }
}
