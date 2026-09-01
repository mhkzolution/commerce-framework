<?php

declare(strict_types=1);

namespace Commerce\Barcode\Services;

use Picqer\Barcode\BarcodeGeneratorSVG;

final class BarcodeLabelRenderer
{
    private BarcodeGeneratorSVG $generator;

    public function __construct()
    {
        $this->generator = new BarcodeGeneratorSVG;
    }

    /**
     * Barcode value is always the SKU (Code128).
     */
    public function svgForSku(string $sku, float $widthFactor = 1.2, ?float $labelHeightMm = null): string
    {
        $sku = trim($sku);

        if ($sku === '') {
            return '';
        }

        $barHeight = $labelHeightMm !== null
            ? max(14, min(44, (int) round($labelHeightMm * 1.1)))
            : 30;

        $svg = $this->generator->getBarcode(
            $sku,
            $this->generator::TYPE_CODE_128,
            $widthFactor,
            (float) $barHeight,
        );

        return $this->makeResponsive($svg);
    }

    private function makeResponsive(string $svg): string
    {
        $svg = preg_replace('/<\?xml[^?]*\?>\s*/', '', $svg) ?? $svg;
        $svg = preg_replace('/<!DOCTYPE[^>]*>\s*/', '', $svg) ?? $svg;

        if (! str_contains($svg, 'preserveAspectRatio')) {
            $svg = preg_replace('/<svg\b/', '<svg preserveAspectRatio="xMidYMid meet"', $svg, 1) ?? $svg;
        }

        return trim($svg);
    }
}
