<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Catalog\DTO\CreateAttributeData;
use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeSet;
use Commerce\Catalog\Services\AttributeService;
use Commerce\Product\Models\Product;
use Illuminate\Support\Str;

final class VariantOptionAttributeProvisioner
{
    public function __construct(
        private readonly AttributeService $attributeService,
    ) {}

    /**
     * @param  list<string>  $optionNames
     * @return array<string, int>
     */
    public function resolve(Product $product, array $optionNames): array
    {
        if ($optionNames === []) {
            return [];
        }

        $map = $this->existingMap($product, $optionNames);
        $missing = array_values(array_filter(
            $optionNames,
            static fn (string $name): bool => ! isset($map[strtolower($name)]),
        ));

        if ($missing === []) {
            return $map;
        }

        $attributeSet = $this->resolveAttributeSet($product);

        foreach ($missing as $optionName) {
            $code = Str::slug($optionName, '_');
            $attribute = Attribute::query()
                ->where('code', $code)
                ->orWhere('name', $optionName)
                ->first();

            if ($attribute === null) {
                $attribute = $this->attributeService->create(new CreateAttributeData(
                    code: $code,
                    name: $optionName,
                    type: 'select',
                    isFilterable: true,
                    isRequired: false,
                    isVisible: true,
                    position: 0,
                    options: [],
                ));
            }

            if ($attributeSet !== null) {
                $maxPosition = (int) $attributeSet->attributes()->max('attribute_set_attributes.position');
                $attributeSet->attributes()->syncWithoutDetaching([
                    $attribute->id => [
                        'position' => $maxPosition + 1,
                        'is_required' => false,
                    ],
                ]);
            }

            $map[strtolower($optionName)] = (int) $attribute->id;
            $map[strtolower($attribute->code)] = (int) $attribute->id;
            $map[strtolower($attribute->name)] = (int) $attribute->id;
        }

        return $map;
    }

    /**
     * @param  list<string>  $optionNames
     * @return array<string, int>
     */
    private function existingMap(Product $product, array $optionNames): array
    {
        $query = Attribute::query();

        if ($product->attribute_set_id !== null) {
            $query->whereHas('attributeSets', function ($inner) use ($product): void {
                $inner->where('attribute_sets.id', $product->attribute_set_id);
            });
        }

        $attributes = $query->get();
        $map = [];

        foreach ($attributes as $attribute) {
            $map[strtolower($attribute->name)] = (int) $attribute->id;
            $map[strtolower($attribute->code)] = (int) $attribute->id;
        }

        return $map;
    }

    private function resolveAttributeSet(Product $product): ?AttributeSet
    {
        if ($product->attribute_set_id !== null) {
            return AttributeSet::query()->find($product->attribute_set_id);
        }

        $set = AttributeSet::query()->where('code', 'variant_options')->first();

        if ($set === null) {
            $set = AttributeSet::query()->create([
                'code' => 'variant_options',
                'name' => 'Variant options',
            ]);
        }

        $product->update(['attribute_set_id' => $set->id]);

        return $set;
    }
}
