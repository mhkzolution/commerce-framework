<?php

declare(strict_types=1);

namespace Tests\Unit\Product;

use Commerce\Product\Support\SearchNormalizer;
use PHPUnit\Framework\TestCase;

final class SearchNormalizerTest extends TestCase
{
    public function test_text_normalize_uses_nfc_and_lowercase(): void
    {
        $composed = 'É';
        $decomposed = "E\u{0301}";
        $this->assertSame(
            SearchNormalizer::textNormalize($composed),
            SearchNormalizer::textNormalize($decomposed),
        );
    }

    public function test_sku_normalize_trim_uppercase_nfc(): void
    {
        $this->assertSame('ABC123', SearchNormalizer::skuNormalize(' abc123 '));
    }

    public function test_tokenize_splits_whitespace_not_hyphens(): void
    {
        $this->assertSame(['tshirt-red', 'tee'], SearchNormalizer::tokenize('  TSHIRT-RED   tee '));
    }

    public function test_tokenize_returns_empty_array_for_empty_input(): void
    {
        $this->assertSame([], SearchNormalizer::tokenize(''));
        $this->assertSame([], SearchNormalizer::tokenize('   '));
    }
}
