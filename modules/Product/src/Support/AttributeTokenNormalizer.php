<?php

declare(strict_types=1);

namespace Commerce\Product\Support;

use Normalizer;

final class AttributeTokenNormalizer
{
    private const SIZE_CODES = ['size_top', 'size_bottom', 'size'];

    /**
     * @return list<string>
     */
    public function tokens(string $raw, string $attributeCode): array
    {
        $nfc = Normalizer::normalize($raw, Normalizer::FORM_C) ?: $raw;
        $parts = preg_split('/\s*[,，]\s*/u', $nfc) ?: [];
        $seen = [];
        $out = [];

        foreach ($parts as $part) {
            $canonical = $this->canonicalize($part, $attributeCode);
            if ($canonical === '') {
                continue;
            }
            $fold = mb_strtolower($canonical);
            if (isset($seen[$fold])) {
                continue;
            }
            $seen[$fold] = true;
            $out[] = $canonical;
        }

        return $out;
    }

    public function canonicalize(string $token, string $attributeCode): string
    {
        $nfc = Normalizer::normalize($token, Normalizer::FORM_C) ?: $token;
        $trimmed = trim($nfc);
        if ($trimmed === '') {
            return '';
        }

        $compact = preg_replace('/\s+/u', '', $trimmed) ?? $trimmed;
        $compact = preg_replace('/\s*-\s*/u', '-', $compact) ?? $compact;

        if ($attributeCode === 'age') {
            return $this->uppercaseLatin($compact);
        }

        if (in_array($attributeCode, self::SIZE_CODES, true)) {
            $compact = preg_replace('/เดือน$/u', 'M', $compact) ?? $compact;
            $compact = preg_replace('/ปี$/u', 'Y', $compact) ?? $compact;
            $compact = preg_replace('/cm$/iu', 'CM', $compact) ?? $compact;

            return $this->uppercaseLatin($compact);
        }

        return $trimmed;
    }

    private function uppercaseLatin(string $token): string
    {
        return preg_replace_callback('/[A-Za-z]+/', static fn (array $m): string => strtoupper($m[0]), $token) ?? $token;
    }
}
