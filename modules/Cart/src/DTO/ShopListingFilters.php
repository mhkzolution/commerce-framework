<?php

declare(strict_types=1);

namespace Commerce\Cart\DTO;

use Commerce\Catalog\Models\Attribute;
use Commerce\Support\SearchReservedParams;
use Illuminate\Http\Request;

final readonly class ShopListingFilters
{
    public ?string $q;

    public ?string $search;

    /**
     * @var array<string, string>
     */
    public array $attributes;

    /**
     * @param  array<string, string>  $attributes
     */
    public function __construct(
        ?string $search = null,
        public ?string $category = null,
        public string $availability = 'all',
        public string $sort = 'latest',
        public ?string $brand = null,
        public ?int $priceMin = null,
        public ?int $priceMax = null,
        public ?string $size = null,
        public ?string $color = null,
        ?string $q = null,
        array $attributes = [],
    ) {
        $this->q = self::nonEmptyString($q) ?? self::nonEmptyString($search);
        $this->search = $this->q;

        $normalizedAttributes = [];
        foreach ($attributes as $code => $value) {
            if (
                is_string($code)
                && ! in_array($code, [...SearchReservedParams::KEYS, 'search'], true)
                && ($normalized = self::nonEmptyString($value)) !== null
            ) {
                $normalizedAttributes[$code] = $normalized;
            }
        }

        if (! isset($normalizedAttributes['size']) && self::nonEmptyString($size) !== null) {
            $normalizedAttributes['size'] = self::nonEmptyString($size);
        }
        if (! isset($normalizedAttributes['color']) && self::nonEmptyString($color) !== null) {
            $normalizedAttributes['color'] = self::nonEmptyString($color);
        }

        $this->attributes = $normalizedAttributes;
    }

    public static function fromRequest(Request $request): self
    {
        $q = self::nonEmptyString($request->input('q'));
        $search = self::nonEmptyString($request->input('search'));
        $category = $request->string('category')->toString() ?: null;
        $availability = $request->string('availability')->toString();
        $sort = $request->string('sort')->toString();
        $brand = $request->string('brand')->toString() ?: null;
        $size = $request->string('size')->toString() ?: null;
        $color = $request->string('color')->toString() ?: null;
        $attributes = self::filterableAttributes($request);

        return new self(
            search: $search,
            category: $category,
            availability: $availability === 'in_stock' ? 'in_stock' : 'all',
            sort: in_array($sort, ['price_asc', 'price_desc'], true) ? $sort : 'latest',
            brand: $brand,
            priceMin: self::optionalInt($request->input('price_min')),
            priceMax: self::optionalInt($request->input('price_max')),
            size: $size,
            color: $color,
            q: $q,
            attributes: $attributes,
        );
    }

    /**
     * @return array<string, string>
     */
    public function toQueryArray(): array
    {
        $query = array_filter([
            'q' => $this->q,
            'category' => $this->category,
            'availability' => $this->availability !== 'all' ? $this->availability : null,
            'sort' => $this->sort !== 'latest' ? $this->sort : null,
            'brand' => $this->brand,
            'price_min' => $this->priceMin !== null ? (string) $this->priceMin : null,
            'price_max' => $this->priceMax !== null ? (string) $this->priceMax : null,
            'size' => $this->size,
            'color' => $this->color,
        ], static fn (?string $value): bool => $value !== null && $value !== '');

        foreach ($this->attributes as $code => $value) {
            $query[$code] = $value;
        }

        return $query;
    }

    /**
     * @param  array<string, int|string|null>  $overrides
     * @return array<string, string>
     */
    public function queryWith(array $overrides): array
    {
        $query = $this->toQueryArray();

        foreach ($overrides as $key => $value) {
            if ($value === null || $value === '') {
                unset($query[$key]);

                continue;
            }

            $query[$key] = (string) $value;
        }

        return $query;
    }

    public function hasListingConstraints(): bool
    {
        return $this->q !== null
            || $this->attributes !== []
            || $this->category !== null
            || $this->brand !== null
            || $this->priceMin !== null
            || $this->priceMax !== null
            || $this->size !== null
            || $this->color !== null
            || $this->availability === 'in_stock';
    }

    public function matchesPricePreset(?int $min, ?int $max): bool
    {
        return $this->priceMin === $min && $this->priceMax === $max;
    }

    private static function optionalInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return max(0, (int) $value);
    }

    /**
     * @return array<string, string>
     */
    private static function filterableAttributes(Request $request): array
    {
        $candidates = [];

        foreach ($request->query() as $code => $value) {
            if (
                ! is_string($code)
                || in_array($code, [...SearchReservedParams::KEYS, 'search', 'size', 'color'], true)
                || ! is_string($value)
                || self::nonEmptyString($value) === null
            ) {
                continue;
            }

            $candidates[$code] = trim($value);
        }

        if ($candidates === []) {
            return [];
        }

        $filterableCodes = Attribute::query()
            ->where('is_filterable', true)
            ->whereIn('code', array_keys($candidates))
            ->pluck('code')
            ->all();

        return array_intersect_key($candidates, array_flip($filterableCodes));
    }

    private static function nonEmptyString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
