<?php

declare(strict_types=1);

namespace Commerce\Product\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class UpdateProductData extends DataTransferObject
{
    /**
     * @param  list<int>  $categoryIds
     * @param  list<int>  $tagIds
     * @param  list<string>  $mediaUuids
     * @param  array<int, mixed>  $attributeValues
     */
    public function __construct(
        public string $name,
        public ?string $slug = null,
        public ?string $description = null,
        public string $status = 'draft',
        public string $visibility = 'public',
        public ?string $brandUuid = null,
        public ?string $sellerUuid = null,
        public ?int $attributeSetId = null,
        public ?string $sku = null,
        public float $price = 0,
        public ?float $compareAtPrice = null,
        public ?string $publishAt = null,
        public array $categoryIds = [],
        public array $tagIds = [],
        public array $mediaUuids = [],
        public array $attributeValues = [],
        public ?SeoData $seo = null,
    ) {}
}
