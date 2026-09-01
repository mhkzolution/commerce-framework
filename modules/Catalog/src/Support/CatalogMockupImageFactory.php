<?php

declare(strict_types=1);

namespace Commerce\Catalog\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

final class CatalogMockupImageFactory
{
    /**
     * @var list<array{0: int, 1: int, 2: int}>
     */
    private const PALETTE = [
        [26, 26, 26],
        [214, 199, 176],
        [37, 99, 235],
        [22, 163, 74],
        [147, 51, 234],
        [234, 88, 12],
        [15, 118, 110],
        [190, 24, 93],
    ];

    public function makeCategoryImage(string $label): UploadedFile
    {
        return $this->makeImage(
            label: $label,
            filename: 'category-'.Str::slug($label).'.png',
            width: 480,
            height: 360,
            subtitle: 'CATEGORY',
        );
    }

    public function makeBrandLogo(string $label): UploadedFile
    {
        $monogram = mb_strtoupper(mb_substr(trim($label), 0, 1));

        return $this->makeImage(
            label: $label,
            filename: 'brand-'.Str::slug($label).'.png',
            width: 400,
            height: 400,
            subtitle: 'BRAND',
            monogram: $monogram,
        );
    }

    public function makeProductImage(string $label): UploadedFile
    {
        return $this->makeImage(
            label: $label,
            filename: 'product-'.Str::slug($label).'.png',
            width: 600,
            height: 800,
            subtitle: 'PRODUCT',
        );
    }

    private function makeImage(
        string $label,
        string $filename,
        int $width,
        int $height,
        string $subtitle,
        ?string $monogram = null,
    ): UploadedFile {
        if (! function_exists('imagecreatetruecolor')) {
            throw new \RuntimeException('GD extension is required to generate catalog mockup images.');
        }

        $image = imagecreatetruecolor($width, $height);

        if ($image === false) {
            throw new \RuntimeException('Could not create mockup image.');
        }

        [$red, $green, $blue] = $this->paletteFor($label);
        $background = imagecolorallocate($image, $red, $green, $blue);
        $foreground = $this->foregroundColor($image, $red, $green, $blue);
        $muted = $this->mutedColor($image, $red, $green, $blue);

        imagefilledrectangle($image, 0, 0, $width, $height, $background);

        if ($monogram !== null) {
            $this->drawCenteredBuiltInString($image, $monogram, 5, $foreground, (int) ($width / 2), (int) ($height / 2) - 16);
        }

        $this->drawCenteredBuiltInString($image, $this->truncate($label, 22), 4, $foreground, (int) ($width / 2), $height - 44);
        $this->drawCenteredBuiltInString($image, $subtitle, 3, $muted, (int) ($width / 2), $height - 24);

        ob_start();
        imagepng($image);
        $contents = (string) ob_get_clean();
        imagedestroy($image);

        $path = tempnam(sys_get_temp_dir(), 'catalog-mockup-');
        if ($path === false) {
            throw new \RuntimeException('Could not create temporary mockup file.');
        }

        file_put_contents($path, $contents);

        return new UploadedFile($path, $filename, 'image/png', null, true);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function paletteFor(string $label): array
    {
        $index = abs(crc32(Str::lower($label))) % count(self::PALETTE);

        return self::PALETTE[$index];
    }

    private function foregroundColor(\GdImage $image, int $red, int $green, int $blue): int
    {
        $luminance = (0.299 * $red) + (0.587 * $green) + (0.114 * $blue);

        return $luminance > 160
            ? imagecolorallocate($image, 26, 26, 26)
            : imagecolorallocate($image, 250, 250, 250);
    }

    private function mutedColor(\GdImage $image, int $red, int $green, int $blue): int
    {
        $luminance = (0.299 * $red) + (0.587 * $green) + (0.114 * $blue);

        return $luminance > 160
            ? imagecolorallocate($image, 82, 82, 82)
            : imagecolorallocate($image, 212, 212, 212);
    }

    private function drawCenteredBuiltInString(
        \GdImage $image,
        string $text,
        int $font,
        int $color,
        int $centerX,
        int $centerY,
    ): void {
        $textWidth = imagefontwidth($font) * strlen($text);
        $textHeight = imagefontheight($font);
        $x = (int) ($centerX - ($textWidth / 2));
        $y = (int) ($centerY - ($textHeight / 2));

        imagestring($image, $font, max(0, $x), max(0, $y), $text, $color);
    }

    private function truncate(string $value, int $length): string
    {
        if (mb_strlen($value) <= $length) {
            return $value;
        }

        return mb_substr($value, 0, $length - 1).'…';
    }
}
