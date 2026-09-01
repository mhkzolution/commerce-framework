<?php

declare(strict_types=1);

namespace Tests\Unit\Barcode;

use Commerce\Barcode\Support\BarcodeLabelStyle;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class BarcodeLabelStyleTest extends TestCase
{
    #[Test]
    public function it_resolves_defaults_when_settings_are_missing(): void
    {
        $style = BarcodeLabelStyle::resolve([]);

        $this->assertSame(1.0, $style['padding_top']);
        $this->assertSame(2.0, $style['padding_right']);
        $this->assertSame(0.2, $style['content_gap']);
        $this->assertSame(6.0, $style['owner_font_size']);
        $this->assertSame(6.0, $style['sku_font_size']);
    }

    #[Test]
    public function it_prefers_explicit_settings(): void
    {
        $style = BarcodeLabelStyle::resolve([
            'label_padding_top' => 3,
            'label_content_gap' => 1.5,
            'label_owner_font_size' => 9,
            'label_sku_font_size' => 8,
        ]);

        $this->assertSame(3.0, $style['padding_top']);
        $this->assertSame(1.5, $style['content_gap']);
        $this->assertSame(9.0, $style['owner_font_size']);
        $this->assertSame(8.0, $style['sku_font_size']);
    }
}
