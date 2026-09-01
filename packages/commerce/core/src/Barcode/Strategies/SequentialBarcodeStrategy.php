<?php

declare(strict_types=1);

namespace Commerce\Core\Barcode\Strategies;

use Commerce\Contracts\Barcode\BarcodeValueGeneratorStrategyInterface;
use Illuminate\Support\Facades\Cache;

final class SequentialBarcodeStrategy implements BarcodeValueGeneratorStrategyInterface
{
    public function name(): string
    {
        return 'sequential';
    }

    public function generate(array $options = []): string
    {
        $prefix = (string) ($options['prefix'] ?? 'BC-');
        $counterKey = (string) ($options['counter_key'] ?? 'barcode.value_generator.sequential');
        $padLength = max(4, min(16, (int) ($options['pad_length'] ?? 8)));

        $next = (int) Cache::increment($counterKey);

        return $prefix.str_pad((string) $next, $padLength, '0', STR_PAD_LEFT);
    }
}
