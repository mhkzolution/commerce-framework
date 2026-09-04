<?php

declare(strict_types=1);

namespace Commerce\Contracts\Storefront;

final readonly class ProductDetailData
{
    /**
     * @param  list<array{type: string, url: string, thumbnail: string, alt: string}>  $gallery
     * @param  list<array{label: string, url?: string}>  $breadcrumbItems
     * @param  list<array{uuid: string, price: int, compare_at_price: ?int, available: int, options: array<string, string>, image_thumbnail: ?string, sku: ?string}>  $variants
     * @param  list<array{key: string, name: string, values: list<string>}>  $variantAxes
     * @param  list<array{label: string, value: string}>  $attributes
     * @param  list<ProductCardData>  $relatedProducts
     */
    public function __construct(
        public string $name,
        public ?string $description,
        public ?string $imageUrl,
        public int $price,
        public ?int $compareAtPrice,
        public string $displayCurrency,
        public ?string $sku,
        public ?int $available,
        public bool $inStock,
        public string $variantUuid,
        public string $shopUrl,
        public string $uuid = '',
        public string $slug = '',
        public ?int $discountPercent = null,
        public array $gallery = [],
        public array $breadcrumbItems = [],
        public array $variants = [],
        public array $variantAxes = [],
        public array $attributes = [],
        public array $relatedProducts = [],
    ) {}
}
