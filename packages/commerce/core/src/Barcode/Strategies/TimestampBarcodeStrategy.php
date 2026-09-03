<?php

declare(strict_types=1);

namespace Commerce\Core\Barcode\Strategies;

use Commerce\Contracts\Barcode\BarcodeValueGeneratorStrategyInterface;

final class TimestampBarcodeStrategy implements BarcodeValueGeneratorStrategyInterface
{
    public function name(): string
    {
        return 'timestamp';
    }

    public function generate(array $options = []): string
    {
        $prefix = (string) ($options['prefix'] ?? '');
        $timestamp = base_convert((string) now()->getTimestampMs(), 10, 36);

        return strtoupper($prefix.$timestamp);
    }
}
