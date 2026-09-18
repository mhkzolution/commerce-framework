<?php

declare(strict_types=1);

namespace Commerce\Cart\DTO;

final readonly class ShopListingContext
{
    /**
     * @param  array<string, string>  $routeParams
     */
    public function __construct(
        public string $routeName,
        public array $routeParams = [],
        public bool $lockBrand = false,
    ) {}

    public static function shop(): self
    {
        return new self('storefront.shop.index');
    }

    public static function brand(string $slug): self
    {
        return new self('storefront.brands.show', ['slug' => $slug], lockBrand: true);
    }

    public function url(): string
    {
        return route($this->routeName, $this->routeParams);
    }

    /**
     * @param  array<string, int|string|null>  $overrides
     */
    public function urlWith(ShopListingFilters $filters, array $overrides = []): string
    {
        return route($this->routeName, [...$this->routeParams, ...$this->query($filters, $overrides)]);
    }

    /**
     * @param  array<string, int|string|null>  $overrides
     * @return array<string, string>
     */
    public function query(ShopListingFilters $filters, array $overrides = []): array
    {
        $query = $filters->queryWith($overrides);

        if ($this->lockBrand) {
            unset($query['brand']);
        }

        return $query;
    }
}
