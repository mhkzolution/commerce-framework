<?php

declare(strict_types=1);

namespace Commerce\Core\Barcode\Strategies;

use Commerce\Contracts\Barcode\BarcodeValueGeneratorStrategyInterface;

final class RandomBarcodeStrategy implements BarcodeValueGeneratorStrategyInterface
{
    private const string CHARSET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function name(): string
    {
        return 'random';
    }

    public function generate(array $options = []): string
    {
        $length = max(4, min(32, (int) ($options['length'] ?? 12)));
        $value = '';

        for ($i = 0; $i < $length; $i++) {
            $value .= self::CHARSET[random_int(0, strlen(self::CHARSET) - 1)];
        }

        return $value;
    }
}
