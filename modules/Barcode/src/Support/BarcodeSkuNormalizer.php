<?php

declare(strict_types=1);

namespace Commerce\Barcode\Support;

final class BarcodeSkuNormalizer
{
    public static function normalize(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        // Strip control characters often appended by barcode scanners.
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value) ?? $value;

        return trim($value);
    }
}
