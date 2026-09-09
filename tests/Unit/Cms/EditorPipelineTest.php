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

    public function test_it_strips_img_onerror_while_keeping_src(): void
    {
        $html = '<img src="x" onerror="alert(1)">';

        $sanitized = $this->pipeline->sanitize($html);

        $this->assertStringNotContainsString('onerror', $sanitized);
        $this->assertStringNotContainsString('alert(1)', $sanitized);
        $this->assertStringContainsString('src="x"', $sanitized);
    }

    public function test_empty_content_is_preserved(): void
    {
        $this->assertNull($this->pipeline->sanitize(null));
        $this->assertSame('', $this->pipeline->sanitize(''));
    }

    public function test_it_keeps_percent_width_and_data_align(): void
    {
        $html = '<img src="/x.jpg" alt="A" width="50%" data-align="center">';

        $this->assertSame($html, $this->pipeline->sanitize($html));
    }

    public function test_it_drops_invalid_image_layout_attributes(): void
    {
        $html = '<img src="/x.jpg" alt="A" width="12%" data-align="justify" style="width:50%" width-px="50">';

        $sanitized = $this->pipeline->sanitize($html);

        $this->assertStringContainsString('src="/x.jpg"', $sanitized);
        $this->assertStringContainsString('alt="A"', $sanitized);
        $this->assertStringNotContainsString('width="12%"', $sanitized);
        $this->assertStringNotContainsString('data-align="justify"', $sanitized);
        $this->assertStringNotContainsString('style=', $sanitized);
    }

    public function test_it_drops_pixel_and_out_of_range_width(): void
    {
        $pixel = $this->pipeline->sanitize('<img src="/x.jpg" alt="" width="50">');
        $over = $this->pipeline->sanitize('<img src="/x.jpg" alt="" width="150%">');

        $this->assertStringNotContainsString('width="50"', $pixel);
        $this->assertStringNotContainsString('width="150%"', $over);
    }

    public function test_legacy_image_without_width_is_kept(): void
    {
        $html = '<img src="/x.jpg" alt="">';

        $this->assertSame($html, $this->pipeline->sanitize($html));
    }

    public function test_it_parses_quoted_attributes_with_whitespace_around_equals(): void
    {
        $html = '<img src= "/x.jpg" alt= "">';

        $sanitized = $this->pipeline->sanitize($html);

        $this->assertSame('<img src="/x.jpg" alt="">', $sanitized);
    }
}
