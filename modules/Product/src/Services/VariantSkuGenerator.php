<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Closure;

final class VariantSkuGenerator
{
    /**
     * @var Closure(): array<int, string>
     */
    private readonly Closure $existingSkuProvider;

    /**
     * @param  callable(): array<int, string>  $existingSkuProvider
     */
    public function __construct(callable $existingSkuProvider)
    {
        $this->existingSkuProvider = Closure::fromCallable($existingSkuProvider);
    }

    /**
     * @param  array<int, string>  $optionValues
     */
    public function allocate(string $prefix, array $optionValues, ?string $typedSku): GeneratedSku
    {
        if ($typedSku !== null && trim($typedSku) !== '') {
            return new GeneratedSku($typedSku, false);
        }

        $parts = array_values(array_filter([
            $this->slugify($prefix) ?: 'SKU',
            ...array_map($this->slugify(...), $optionValues),
        ], static fn (string $part): bool => $part !== ''));
        $baseSku = implode('-', $parts);
        $existingSkus = array_fill_keys(($this->existingSkuProvider)(), true);
        $sku = $baseSku;
        $suffix = 2;

        while (isset($existingSkus[$sku])) {
            $sku = $baseSku.'-'.$suffix;
            $suffix++;
        }

        return new GeneratedSku($sku, true);
    }

    private function slugify(string $value): string
    {
        return trim((string) preg_replace('/[^A-Z0-9]+/', '-', strtoupper($value)), '-');
    }
}
