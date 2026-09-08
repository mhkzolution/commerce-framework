<?php

declare(strict_types=1);

namespace Commerce\Product\Support;

use InvalidArgumentException;
use Normalizer;

final class SearchNormalizer
{
    public static function nfc(string $value): string
    {
        $normalized = Normalizer::normalize($value, Normalizer::FORM_C);

        if ($normalized === false) {
            throw new InvalidArgumentException('Unable to normalize string to NFC.');
        }

        return $normalized;
    }

    public static function skuNormalize(string $value): string
    {
        return mb_strtoupper(trim(self::nfc($value)), 'UTF-8');
    }

    public static function textNormalize(string $value): string
    {
        return mb_strtolower(trim(self::nfc($value)), 'UTF-8');
    }

    /**
     * @return list<string>
     */
    public static function tokenize(string $value): array
    {
        $normalized = self::textNormalize($value);

        if ($normalized === '') {
            return [];
        }

        $parts = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        return $parts === false ? [] : array_values($parts);
    }
}
