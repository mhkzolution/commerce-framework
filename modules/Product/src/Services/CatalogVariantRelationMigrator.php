<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Catalog\Services\AttributeValueService;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttribute;
use Commerce\Product\Models\ProductAttributeValue;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final class CatalogVariantRelationMigrator
{
    public function __construct(
        private readonly AttributeValueService $attributeValueService,
        private readonly ProductAttributeSetSync $attributeSetSync,
    ) {}

    public function migrate(Product $product): void
    {
        $this->attributeSetSync->syncProductAttributesFromSet($product);

        $product->refresh();
        $product->load(['attributeSet.attributes.values', 'variants']);

        if ($product->type === 'variable') {
            $this->migrateVariationAxes($product);
        }

        $this->fillExistingPavValueIds($product);
    }

    private function migrateVariationAxes(Product $product): void
    {
        /** @var Collection<int, Attribute> $setAttributes */
        $setAttributes = $product->attributeSet?->attributes ?? collect();

        foreach ($this->collectAxes($product) as $axis) {
            $attribute = $this->matchSetAttribute($setAttributes, $axis['name']);

            if ($attribute === null) {
                $this->logUnmatchedAxis($product, $axis['name'], $axis['values']);

                continue;
            }

            ProductAttribute::query()
                ->where('product_id', $product->id)
                ->where('attribute_id', $attribute->id)
                ->update(['used_for_variations' => true]);

            foreach ($axis['values'] as $option) {
                $attributeValue = $this->resolveAttributeValue($attribute, $option);
                $this->upsertPav($product, $attribute, null, $attributeValue, $option);
            }

            foreach ($product->variants as $variant) {
                $this->migrateVariantAxis($product, $variant, $attribute, $axis['name']);
            }
        }
    }

    /**
     * @param  list<string>  $values
     */
    private function logUnmatchedAxis(Product $product, string $attributeName, array $values): void
    {
        $optionNames = $values === [] ? [''] : $values;

        foreach ($optionNames as $optionName) {
            Log::warning('catalog.variant_relation.unmatched_option', [
                'product_id' => $product->id,
                'attribute_name' => $attributeName,
                'option_name' => $optionName,
            ]);
        }
    }

    private function migrateVariantAxis(
        Product $product,
        ProductVariant $variant,
        Attribute $attribute,
        string $axisName,
    ): void {
        $options = is_array($variant->meta['options'] ?? null) ? $variant->meta['options'] : [];
        $option = $this->optionValueFromVariant($options, $attribute, $axisName);

        if ($option === null) {
            return;
        }

        $attributeValue = $this->resolveAttributeValue($attribute, $option);
        $this->upsertPav($product, $attribute, $variant->id, $attributeValue, $option);
    }

    /**
     * @return list<array{name: string, values: list<string>}>
     */
    private function collectAxes(Product $product): array
    {
        /** @var array<string, array{name: string, values: list<string>}> $axes */
        $axes = [];

        $variantOptions = $product->meta['variant_options'] ?? [];
        if (is_array($variantOptions)) {
            foreach ($variantOptions as $option) {
                if (! is_array($option)) {
                    continue;
                }

                $name = trim((string) ($option['name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $rawValues = $option['values'] ?? [];
                $axes[$this->normalizeKey($name)] = [
                    'name' => $name,
                    'values' => $this->uniqueValues(is_array($rawValues) ? $rawValues : []),
                ];
            }
        }

        foreach ($product->variants as $variant) {
            $options = $variant->meta['options'] ?? [];
            if (! is_array($options)) {
                continue;
            }

            foreach ($options as $key => $value) {
                $name = trim((string) $key);
                if ($name === '') {
                    continue;
                }

                $axisKey = $this->normalizeKey($name);
                if (! isset($axes[$axisKey])) {
                    $axes[$axisKey] = [
                        'name' => $name,
                        'values' => [],
                    ];
                }

                if (is_scalar($value) && trim((string) $value) !== '') {
                    $axes[$axisKey]['values'][] = trim((string) $value);
                }
            }
        }

        foreach ($axes as $axisKey => $axis) {
            $axes[$axisKey]['values'] = $this->uniqueValues($axis['values']);
        }

        return array_values($axes);
    }

    /**
     * @param  Collection<int, Attribute>  $setAttributes
     */
    private function matchSetAttribute(Collection $setAttributes, string $axisName): ?Attribute
    {
        $needle = $this->normalizeKey($axisName);

        return $setAttributes->first(function (Attribute $attribute) use ($needle): bool {
            return $this->normalizeKey($attribute->name) === $needle
                || $this->normalizeKey($attribute->code) === $needle;
        });
    }

    /**
     * @param  array<array-key, mixed>  $options
     */
    private function optionValueFromVariant(array $options, Attribute $attribute, string $axisName): ?string
    {
        $candidates = [$axisName, $attribute->name, $attribute->code];

        foreach ($options as $key => $value) {
            if (! is_scalar($value)) {
                continue;
            }

            $trimmed = trim((string) $value);
            if ($trimmed === '') {
                continue;
            }

            foreach ($candidates as $candidate) {
                if ($this->normalizeKey((string) $key) === $this->normalizeKey($candidate)) {
                    return $trimmed;
                }
            }
        }

        return null;
    }

    private function resolveAttributeValue(Attribute $attribute, string $option): AttributeValue
    {
        $existing = AttributeValue::query()
            ->where('attribute_id', $attribute->id)
            ->where(function ($query) use ($option): void {
                $query->whereRaw('LOWER(label) = ?', [$this->normalizeKey($option)])
                    ->orWhereRaw('LOWER(code) = ?', [$this->normalizeKey($option)]);
            })
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $maxPosition = AttributeValue::query()
            ->where('attribute_id', $attribute->id)
            ->max('position');

        return AttributeValue::query()->create([
            'tenant_id' => $attribute->tenant_id,
            'attribute_id' => $attribute->id,
            'code' => $this->attributeValueService->allocateCode($attribute->id, $option),
            'label' => $option,
            'position' => $maxPosition === null ? 0 : ((int) $maxPosition + 1),
        ]);
    }

    private function upsertPav(
        Product $product,
        Attribute $attribute,
        ?int $variantId,
        AttributeValue $attributeValue,
        string $option,
    ): void {
        $query = ProductAttributeValue::query()
            ->where('product_id', $product->id)
            ->where('attribute_id', $attribute->id)
            ->when(
                $variantId === null,
                static fn ($inner) => $inner->whereNull('product_variant_id'),
                static fn ($inner) => $inner->where('product_variant_id', $variantId),
            );

        $existing = (clone $query)->where('attribute_value_id', $attributeValue->id)->first()
            ?? (clone $query)
                ->whereNull('attribute_value_id')
                ->whereRaw('LOWER(value) = ?', [$this->normalizeKey($option)])
                ->first();

        if ($existing !== null) {
            if ($existing->attribute_value_id !== $attributeValue->id) {
                $existing->update(['attribute_value_id' => $attributeValue->id]);
            }

            return;
        }

        ProductAttributeValue::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
            'product_variant_id' => $variantId,
            'attribute_value_id' => $attributeValue->id,
            'value' => $option,
        ]);
    }

    private function fillExistingPavValueIds(Product $product): void
    {
        $setAttributeIds = $product->attributeSet?->attributes->pluck('id')->all() ?? [];
        if ($setAttributeIds === []) {
            return;
        }

        $rows = ProductAttributeValue::query()
            ->where('product_id', $product->id)
            ->whereNull('attribute_value_id')
            ->whereIn('attribute_id', $setAttributeIds)
            ->whereNotNull('value')
            ->get();

        foreach ($rows as $row) {
            $option = trim((string) $row->value);
            if ($option === '' || str_starts_with($option, '[')) {
                continue;
            }

            $attribute = $product->attributeSet?->attributes->firstWhere('id', $row->attribute_id);
            if ($attribute === null) {
                continue;
            }

            $attributeValue = $this->resolveAttributeValue($attribute, $option);
            $row->update(['attribute_value_id' => $attributeValue->id]);
        }
    }

    /**
     * @param  list<mixed>  $values
     * @return list<string>
     */
    private function uniqueValues(array $values): array
    {
        $seen = [];
        $unique = [];

        foreach ($values as $value) {
            if (! is_scalar($value)) {
                continue;
            }

            $trimmed = trim((string) $value);
            if ($trimmed === '') {
                continue;
            }

            $key = $this->normalizeKey($trimmed);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $trimmed;
        }

        return $unique;
    }

    private function normalizeKey(string $value): string
    {
        return mb_strtolower(trim($value));
    }
}
