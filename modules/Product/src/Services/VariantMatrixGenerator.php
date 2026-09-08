<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Catalog\Models\AttributeValue;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttributeValue;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Support\Collection;

final class VariantMatrixGenerator
{
    /**
     * @return array{
     *     keep: array<string, ProductVariant>,
     *     create: array<string, ProductVariant>,
     *     drop: array<string, ProductVariant>
     * }
     */
    public function generate(Product $product): array
    {
        $axes = $product->productAttributes()
            ->where('used_for_variations', true)
            ->orderBy('position')
            ->get();

        /** @var list<int> $axisIds */
        $axisIds = $axes->pluck('attribute_id')->map(static fn ($id): int => (int) $id)->all();

        $membershipByAxis = $this->membershipByAxis($product, $axisIds);
        $desiredCombos = $this->desiredCombos($axisIds, $membershipByAxis);
        $existingByKey = $this->existingVariantsByIdentity($product, $axisIds);

        $keep = [];
        $create = [];
        $drop = [];
        $nextPosition = (int) $product->variants()->max('position');

        foreach ($desiredCombos as $key => $combo) {
            if (isset($existingByKey[$key])) {
                $keep[$key] = $existingByKey[$key];

                continue;
            }

            $nextPosition++;
            $create[$key] = $this->persistVariant($product, $combo, $nextPosition);
        }

        foreach ($existingByKey as $key => $variant) {
            if (! isset($desiredCombos[$key])) {
                $drop[$key] = $variant;
            }
        }

        return [
            'keep' => $keep,
            'create' => $create,
            'drop' => $drop,
        ];
    }

    /**
     * @param  list<int>  $axisIds
     * @return array<int, list<int>>
     */
    private function membershipByAxis(Product $product, array $axisIds): array
    {
        if ($axisIds === []) {
            return [];
        }

        $rows = ProductAttributeValue::query()
            ->where('product_id', $product->id)
            ->whereIn('attribute_id', $axisIds)
            ->whereNull('product_variant_id')
            ->whereNotNull('attribute_value_id')
            ->orderBy('attribute_value_id')
            ->get();

        $membership = [];
        foreach ($axisIds as $attributeId) {
            $membership[$attributeId] = $rows
                ->where('attribute_id', $attributeId)
                ->pluck('attribute_value_id')
                ->map(static fn ($id): int => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        return $membership;
    }

    /**
     * @param  list<int>  $axisIds
     * @param  array<int, list<int>>  $membershipByAxis
     * @return array<string, list<array{attribute_id: int, attribute_value_id: int}>>
     */
    private function desiredCombos(array $axisIds, array $membershipByAxis): array
    {
        if ($axisIds === []) {
            return [];
        }

        foreach ($membershipByAxis as $valueIds) {
            if ($valueIds === []) {
                return [];
            }
        }

        $combos = [];
        foreach ($this->cartesian($axisIds, $membershipByAxis) as $combo) {
            $key = VariantIdentity::key(array_column($combo, 'attribute_value_id'));
            $combos[$key] = $combo;
        }

        return $combos;
    }

    /**
     * @param  list<int>  $axisIds
     * @param  array<int, list<int>>  $membershipByAxis
     * @return list<list<array{attribute_id: int, attribute_value_id: int}>>
     */
    private function cartesian(array $axisIds, array $membershipByAxis): array
    {
        $result = [[]];

        foreach ($axisIds as $attributeId) {
            $next = [];
            foreach ($result as $prefix) {
                foreach ($membershipByAxis[$attributeId] as $valueId) {
                    $next[] = [
                        ...$prefix,
                        [
                            'attribute_id' => $attributeId,
                            'attribute_value_id' => $valueId,
                        ],
                    ];
                }
            }
            $result = $next;
        }

        return $result;
    }

    /**
     * @param  list<int>  $axisIds
     * @return array<string, ProductVariant>
     */
    private function existingVariantsByIdentity(Product $product, array $axisIds): array
    {
        $variants = $product->variants()->get();
        if ($variants->isEmpty()) {
            return [];
        }

        /** @var Collection<int, Collection<int, ProductAttributeValue>> $valuesByVariant */
        $valuesByVariant = ProductAttributeValue::query()
            ->where('product_id', $product->id)
            ->whereIn('product_variant_id', $variants->modelKeys())
            ->whereNotNull('attribute_value_id')
            ->when(
                $axisIds !== [],
                static fn ($query) => $query->whereIn('attribute_id', $axisIds),
                static fn ($query) => $query->whereRaw('0 = 1'),
            )
            ->get()
            ->groupBy('product_variant_id');

        $existing = [];
        foreach ($variants as $variant) {
            $valueIds = ($valuesByVariant->get($variant->id) ?? collect())
                ->pluck('attribute_value_id')
                ->map(static fn ($id): int => (int) $id)
                ->unique()
                ->values()
                ->all();

            $key = VariantIdentity::key($valueIds);
            if (! isset($existing[$key])) {
                $existing[$key] = $variant;
            }
        }

        return $existing;
    }

    /**
     * @param  list<array{attribute_id: int, attribute_value_id: int}>  $combo
     */
    private function persistVariant(Product $product, array $combo, int $position): ProductVariant
    {
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'tenant_id' => $product->tenant_id,
            'sku' => null,
            'name' => $product->name,
            'price' => $product->defaultVariant()?->price ?? 0,
            'is_default' => false,
            'position' => $position,
        ]);

        $labels = AttributeValue::query()
            ->whereIn('id', array_column($combo, 'attribute_value_id'))
            ->pluck('label', 'id');

        foreach ($combo as $item) {
            $existing = ProductAttributeValue::query()
                ->where('product_id', $product->id)
                ->where('attribute_id', $item['attribute_id'])
                ->where('product_variant_id', $variant->id)
                ->where('attribute_value_id', $item['attribute_value_id'])
                ->first();

            if ($existing !== null) {
                continue;
            }

            ProductAttributeValue::query()->create([
                'product_id' => $product->id,
                'attribute_id' => $item['attribute_id'],
                'product_variant_id' => $variant->id,
                'attribute_value_id' => $item['attribute_value_id'],
                'value' => $labels[$item['attribute_value_id']] ?? null,
            ]);
        }

        return $variant;
    }
}
