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
    ) {}

    public static function fromRequest(Request $request): self
    {
        $search = $request->string('search')->toString() ?: null;
        $category = $request->string('category')->toString() ?: null;
        $availability = $request->string('availability')->toString();
        $sort = $request->string('sort')->toString();

        return new self(
            search: $search,
            category: $category,
            availability: $availability === 'in_stock' ? 'in_stock' : 'all',
            sort: in_array($sort, ['price_asc', 'price_desc'], true) ? $sort : 'latest',
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
        ], static fn (?string $value): bool => $value !== null && $value !== '');
    }
}
