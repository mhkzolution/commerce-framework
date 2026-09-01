<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Catalog\DTO\CreateAttributeData;
use Commerce\Catalog\DTO\UpdateAttributeData;
use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeSet;
use Commerce\Catalog\Services\AttributeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class VariantOptionPresetService
{
    public function __construct(
        private readonly AttributeService $attributeService,
    ) {}

    public function attributeSetCode(): string
    {
        return (string) config('product.variant_presets.attribute_set_code', 'variant_presets');
    }

    public function attributeSetName(): string
    {
        return (string) config('product.variant_presets.attribute_set_name', 'ตัวเลือก Variant');
    }

    public function attributeSet(): AttributeSet
    {
        $code = $this->attributeSetCode();

        $set = AttributeSet::query()->where('code', $code)->first();

        if ($set !== null) {
            return $set;
        }

        return AttributeSet::query()->create([
            'code' => $code,
            'name' => $this->attributeSetName(),
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, Attribute>
     */
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        $set = $this->attributeSet();

        return Attribute::query()
            ->whereHas('attributeSets', static function ($query) use ($set): void {
                $query->where('attribute_sets.id', $set->id);
            })
            ->orderBy('position')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, Attribute>
     */
    public function allOrdered(): Collection
    {
        $set = $this->attributeSet();

        return $set->attributes()->get();
    }

    /**
     * @return array<string, list<string>>
     */
    public function presetMap(): array
    {
        $map = [];

        foreach ($this->allOrdered() as $attribute) {
            $options = array_values(array_filter(
                array_map(static fn ($value): string => trim((string) $value), $attribute->options ?? []),
                static fn (string $value): bool => $value !== '',
            ));

            if ($options === []) {
                continue;
            }

            $map[$attribute->name] = $options;
        }

        return $map;
    }

    /**
     * @param  list<string>  $options
     */
    public function create(string $name, string $code, array $options, int $position = 0): Attribute
    {
        $set = $this->attributeSet();

        $attribute = $this->attributeService->create(new CreateAttributeData(
            code: $code,
            name: $name,
            type: 'select',
            isFilterable: true,
            isRequired: false,
            isVisible: true,
            position: $position,
            options: $this->normalizeOptions($options),
        ));

        $set->attributes()->syncWithoutDetaching([
            $attribute->id => [
                'position' => $position,
                'is_required' => false,
            ],
        ]);

        return $attribute;
    }

    /**
     * @param  list<string>  $options
     */
    public function update(Attribute $attribute, string $name, string $code, array $options, int $position): Attribute
    {
        $this->attributeService->update($attribute->uuid, new UpdateAttributeData(
            code: $code,
            name: $name,
            type: 'select',
            isFilterable: true,
            isRequired: false,
            isVisible: true,
            position: $position,
            options: $this->normalizeOptions($options),
        ));

        $set = $this->attributeSet();
        $set->attributes()->syncWithoutDetaching([
            $attribute->id => [
                'position' => $position,
                'is_required' => false,
            ],
        ]);

        return $attribute->fresh() ?? $attribute;
    }

    public function delete(Attribute $attribute): void
    {
        $set = $this->attributeSet();
        $set->attributes()->detach($attribute->id);
        $this->attributeService->delete($attribute->uuid);
    }

    public function suggestCode(string $name): string
    {
        $base = Str::slug($name, '_');

        if ($base === '') {
            $base = 'option';
        }

        $code = $base;
        $suffix = 1;

        while (Attribute::query()->where('code', $code)->exists()) {
            $code = $base.'_'.$suffix;
            $suffix++;
        }

        return $code;
    }

    public function belongsToPresetSet(Attribute $attribute): bool
    {
        $set = $this->attributeSet();

        return $attribute->attributeSets()
            ->where('attribute_sets.id', $set->id)
            ->exists();
    }

    /**
     * @param  list<string>  $options
     * @return list<string>
     */
    private function normalizeOptions(array $options): array
    {
        $normalized = [];

        foreach ($options as $option) {
            $value = trim((string) $option);

            if ($value === '' || in_array($value, $normalized, true)) {
                continue;
            }

            $normalized[] = $value;
        }

        return $normalized;
    }
}
