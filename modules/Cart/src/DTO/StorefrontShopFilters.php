<?php

declare(strict_types=1);

namespace Commerce\Cart\DTO;

use Illuminate\Http\Request;

final readonly class StorefrontShopFilters
{
    public function __construct(
        public ?string $search = null,
        public ?string $category = null,
        public ?string $collection = null,
        public ?string $brand = null,
        public ?float $priceMin = null,
        public ?float $priceMax = null,
        public ?string $size = null,
        public ?string $color = null,
        public ?string $age = null,
        public ?string $gender = null,
        public string $sort = 'newest',
        public int $page = 1,
        public int $perPage = 24,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $sort = $request->string('sort')->toString() ?: 'newest';
        if (! in_array($sort, ['newest', 'price_asc', 'price_desc', 'name_asc', 'rating'], true)) {
            $sort = 'newest';
        }

        $priceMin = $request->filled('price_min') ? (float) $request->input('price_min') : null;
        $priceMax = $request->filled('price_max') ? (float) $request->input('price_max') : null;

        return new self(
            search: $request->string('search')->toString() ?: null,
            category: $request->string('category')->toString() ?: null,
            collection: $request->string('collection')->toString() ?: null,
            brand: $request->string('brand')->toString() ?: null,
            priceMin: $priceMin,
            priceMax: $priceMax,
            size: $request->string('size')->toString() ?: null,
            color: $request->string('color')->toString() ?: null,
            age: $request->string('age')->toString() ?: null,
            gender: $request->string('gender')->toString() ?: null,
            sort: $sort,
            page: max(1, (int) $request->input('page', 1)),
            perPage: min(48, max(12, (int) $request->input('per_page', 24))),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toQueryArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'category' => $this->category,
            'collection' => $this->collection,
            'brand' => $this->brand,
            'price_min' => $this->priceMin,
            'price_max' => $this->priceMax,
            'size' => $this->size,
            'color' => $this->color,
            'age' => $this->age,
            'gender' => $this->gender,
            'sort' => $this->sort !== 'newest' ? $this->sort : null,
            'per_page' => $this->perPage !== 24 ? $this->perPage : null,
        ], static fn ($value) => $value !== null && $value !== '');
    }

    public function matchesPricePreset(?float $min, ?float $max): bool
    {
        $currentMin = $this->priceMin ?? 0.0;
        $currentMax = $this->priceMax;
        $presetMin = $min ?? 0.0;
        $presetMax = $max;

        if ($presetMax === null) {
            return $currentMax === null && abs($currentMin - $presetMin) < 0.01;
        }

        return abs($currentMin - $presetMin) < 0.01
            && $currentMax !== null
            && abs($currentMax - $presetMax) < 0.01;
    }
}
