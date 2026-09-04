<?php

declare(strict_types=1);

namespace Tests\Unit\Theme;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class NotoSansThaiFontTest extends TestCase
{
    public function test_theme_uses_noto_sans_thai_as_the_sans_font(): void
    {
        $css = file_get_contents(base_path('resources/css/app.css'));
        $this->assertNotFalse($css);
        $this->assertStringContainsString("'Noto Sans Thai'", $css);
        $this->assertStringNotContainsString("'Instrument Sans'", $css);
    }

    public function test_pos_and_scanner_shells_use_noto_sans_thai(): void
    {
        foreach (['resources/css/pos.css', 'resources/css/scanner.css'] as $relative) {
            $css = file_get_contents(base_path($relative));
            $this->assertNotFalse($css, $relative);
            $this->assertStringContainsString('Noto Sans Thai', $css, $relative);
            $this->assertStringNotContainsString("'Prompt'", $css, $relative);
        }
    }

    #[DataProvider('layoutProvider')]
    public function test_html_layouts_load_noto_sans_thai(string $relative): void
    {
        $html = file_get_contents(base_path($relative));
        $this->assertNotFalse($html, $relative);
        $this->assertStringContainsString('x-app-fonts', $html, $relative);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function layoutProvider(): array
    {
        return [
            'admin' => ['resources/views/layouts/admin.blade.php'],
            'storefront' => ['modules/Cart/resources/views/layouts/storefront.blade.php'],
            'pos' => ['modules/Pos/resources/views/layouts/pos.blade.php'],
            'scanner' => ['modules/WarehouseScanner/resources/views/layouts/scanner.blade.php'],
            'login' => ['modules/Iam/resources/views/auth/login.blade.php'],
            'two-factor' => ['modules/Iam/resources/views/auth/two-factor.blade.php'],
        ];
    }

    public function test_vite_does_not_load_instrument_sans(): void
    {
        $config = file_get_contents(base_path('vite.config.js'));
        $this->assertNotFalse($config);
        $this->assertStringNotContainsString('Instrument Sans', $config);
    }
}
