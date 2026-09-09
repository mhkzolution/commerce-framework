<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Catalog\Services\AttributeValueService;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttributeValue;
use Commerce\Product\Support\AttributeTokenNormalizer;
use Illuminate\Support\Facades\DB;

final class ProductAttributeValueLinker
{
    public function __construct(
        private readonly AttributeTokenNormalizer $normalizer,
        private readonly AttributeValueService $attributeValueService,
    ) {}

    /**
     * @param  array<int, mixed>  $valuesByAttributeId  attribute id => raw string|list
     */
    public function syncProductLevel(
        Product $product,
        array $valuesByAttributeId,
        bool $replaceUnusedAttributes = false,
    ): void {
        DB::transaction(function () use ($product, $valuesByAttributeId, $replaceUnusedAttributes): void {
            Product::query()
                ->whereKey($product->getKey())
                ->lockForUpdate()
                ->first();

            if ($replaceUnusedAttributes) {
                $submittedAttributeIds = array_map(
                    static fn (int|string $attributeId): int => (int) $attributeId,
                    array_keys($valuesByAttributeId),
                );

                $staleAttributes = ProductAttributeValue::query()
                    ->where('product_id', $product->id)
                    ->whereNull('product_variant_id');

                if ($submittedAttributeIds !== []) {
                    $staleAttributes->whereNotIn('attribute_id', $submittedAttributeIds);
                }

                $staleAttributes->delete();
            }

            foreach ($valuesByAttributeId as $attributeId => $rawValues) {
                $attribute = Attribute::query()->find((int) $attributeId);
                if ($attribute === null) {
                    continue;
                }

                $tokens = [];
                $seen = [];
                $values = is_array($rawValues) ? $rawValues : [$rawValues];

                foreach ($values as $rawValue) {
                    if (! is_scalar($rawValue) && $rawValue !== null) {
                        continue;
                    }

                    foreach ($this->normalizer->tokens((string) $rawValue, $attribute->code) as $token) {
                        $folded = mb_strtolower($token);
                        if (isset($seen[$folded])) {
                            continue;
                        }

                        $seen[$folded] = true;
                        $tokens[] = $token;
                    }
                }

                $valueIds = [];
                foreach ($tokens as $token) {
                    $valueIds[] = $this->resolveOrCreateAttributeValue($attribute, $token)->id;
                }

                $this->syncProductLevelValues($product, $attribute, $valueIds);
            }
        });
    }

    /**
     * @param  list<int>  $valueIds
     */
    public function syncProductLevelValues(Product $product, Attribute $attribute, array $valueIds): void
    {
        $keepIds = [];
        foreach ($valueIds as $valueId) {
            $value = AttributeValue::query()
                ->where('attribute_id', $attribute->id)
                ->whereKey($valueId)
                ->first();
            if ($value === null) {
                continue;
            }

            $keepIds[] = $value->id;
            $this->upsertProductAttributeValue($product, $attribute->id, $value);
        }

        $stale = ProductAttributeValue::query()
            ->where('product_id', $product->id)
            ->where('attribute_id', $attribute->id)
            ->whereNull('product_variant_id');

        if ($keepIds === []) {
            $stale->delete();

            return;
        }

        $stale->where(function ($query) use ($keepIds): void {
            $query->whereNotIn('attribute_value_id', $keepIds)
                ->orWhereNull('attribute_value_id');
        })->delete();

        $this->collapseDuplicateProductLevelValues($product, $attribute, $keepIds);
    }

    public function resolveOrCreateAttributeValue(Attribute $attribute, string $label): AttributeValue
    {
        $existing = AttributeValue::query()
            ->where('attribute_id', $attribute->id)
            ->whereRaw('LOWER(label) = ?', [mb_strtolower($label)])
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $maxPosition = (int) AttributeValue::query()
            ->where('attribute_id', $attribute->id)
            ->max('position');

        return AttributeValue::query()->create([
            'tenant_id' => $attribute->tenant_id,
            'attribute_id' => $attribute->id,
            'code' => $this->attributeValueService->allocateCode($attribute->id, $label),
            'label' => $label,
            'position' => $maxPosition + 1,
        ]);
    }

    private function upsertProductAttributeValue(Product $product, int $attributeId, AttributeValue $value): void
    {
        $existing = ProductAttributeValue::query()
            ->where('product_id', $product->id)
            ->where('attribute_id', $attributeId)
            ->whereNull('product_variant_id')
            ->where('attribute_value_id', $value->id)
            ->first();

        if ($existing !== null) {
            if ($existing->value !== $value->label) {
                $existing->update(['value' => $value->label]);
            }

            return;
        }

        ProductAttributeValue::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $attributeId,
            'product_variant_id' => null,
            'attribute_value_id' => $value->id,
            'value' => $value->label,
        ]);
    }

    /**
     * @param  list<int>  $keepIds
     */
    private function collapseDuplicateProductLevelValues(
        Product $product,
        Attribute $attribute,
        array $keepIds,
    ): void {
        $duplicateIds = ProductAttributeValue::query()
            ->where('product_id', $product->id)
            ->where('attribute_id', $attribute->id)
            ->whereNull('product_variant_id')
            ->whereIn('attribute_value_id', $keepIds)
            ->orderBy('id')
            ->get(['id', 'attribute_value_id'])
            ->groupBy('attribute_value_id')
            ->flatMap(static fn ($rows) => $rows->skip(1)->pluck('id'))
            ->all();

        if ($duplicateIds !== []) {
            ProductAttributeValue::query()->whereKey($duplicateIds)->delete();
        }
    }
}
