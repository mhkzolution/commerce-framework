<?php

declare(strict_types=1);

namespace Tests\Unit\Cms;

use Commerce\Cms\Services\BlogContentFormatter;
use Tests\TestCase;

final class BlogContentFormatterTest extends TestCase
{
    public function test_html_without_headings_is_rendered_not_escaped(): void
    {
        $html = '<p>Hello world.</p><img src="/media/hero.jpg" alt="Hero"><blockquote><p>Quoted</p></blockquote>';

        $formatted = (new BlogContentFormatter)->format($html);

        $this->assertStringContainsString('<p>Hello world.</p>', $formatted['html']);
        $this->assertStringContainsString('<img src="/media/hero.jpg" alt="Hero">', $formatted['html']);
        $this->assertStringContainsString('<blockquote>', $formatted['html']);
        $this->assertStringNotContainsString('&lt;p&gt;', $formatted['html']);
        $this->assertStringNotContainsString('&lt;img', $formatted['html']);
    }

    public function test_plain_text_is_escaped(): void
    {
        $formatted = (new BlogContentFormatter)->format('Hello <script>alert(1)</script>');

        $this->assertStringContainsString('Hello &lt;script&gt;alert(1)&lt;/script&gt;', $formatted['html']);
    }

    public function test_html_headings_receive_toc_ids(): void
    {
        $formatted = (new BlogContentFormatter)->format('<h2>Boxes</h2><p>Pack carefully.</p>');

        $this->assertNotEmpty($formatted['toc']);
        $this->assertStringContainsString('id="section-1-boxes"', $formatted['html']);
        $this->assertStringContainsString('<p>Pack carefully.</p>', $formatted['html']);
    }
}
