<?php

declare(strict_types=1);

namespace Commerce\Cart\DTO;

use Illuminate\Http\Request;

final readonly class ShopListingFilters
{
    public function __construct(
        public ?string $search = null,
        public ?string $category = null,
        public string $availability = 'all',
        public string $sort = 'latest',
        public ?string $brand = null,
        public ?int $priceMin = null,
        public ?int $priceMax = null,
        public ?string $size = null,
        public ?string $color = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $search = $request->string('search')->toString() ?: null;
        $category = $request->string('category')->toString() ?: null;
        $availability = $request->string('availability')->toString();
        $sort = $request->string('sort')->toString();
        $brand = $request->string('brand')->toString() ?: null;
        $size = $request->string('size')->toString() ?: null;
        $color = $request->string('color')->toString() ?: null;

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
        );
    }

    /**
     * @return array<string, string>
     */
    public function toQueryArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'category' => $this->category,
            'availability' => $this->availability !== 'all' ? $this->availability : null,
            'sort' => $this->sort !== 'latest' ? $this->sort : null,
            'brand' => $this->brand,
            'price_min' => $this->priceMin !== null ? (string) $this->priceMin : null,
            'price_max' => $this->priceMax !== null ? (string) $this->priceMax : null,
            'size' => $this->size,
            'color' => $this->color,
        ], static fn (?string $value): bool => $value !== null && $value !== '');
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
        return $this->search !== null
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
}
