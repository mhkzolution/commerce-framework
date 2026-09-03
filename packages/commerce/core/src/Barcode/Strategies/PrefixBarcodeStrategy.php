<?php

declare(strict_types=1);

namespace Commerce\Core\Barcode\Strategies;

use Commerce\Contracts\Barcode\BarcodeValueGeneratorStrategyInterface;

final class PrefixBarcodeStrategy implements BarcodeValueGeneratorStrategyInterface
{
    private const string CHARSET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function name(): string
    {
        return 'prefix';
    }

    public function generate(array $options = []): string
    {
        $prefix = (string) ($options['prefix'] ?? 'BC-');
        $length = max(4, min(24, (int) ($options['length'] ?? 8)));
        $suffix = '';

        for ($i = 0; $i < $length; $i++) {
            $suffix .= self::CHARSET[random_int(0, strlen(self::CHARSET) - 1)];
        }

        return $prefix.$suffix;
    }
}
