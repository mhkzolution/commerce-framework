<?php

declare(strict_types=1);

namespace Commerce\Product\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class CreateProductData extends DataTransferObject
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
        public string $type = 'simple',
        public string $status = 'draft',
        public string $visibility = 'public',
        public ?string $brandUuid = null,
        public ?string $sellerUuid = null,
        public ?int $attributeSetId = null,
        public ?string $sku = null,
        public int $price = 0,
        public ?int $compareAtPrice = null,
        public ?string $publishAt = null,
        public array $categoryIds = [],
        public array $tagIds = [],
        public array $mediaUuids = [],
        public array $attributeValues = [],
        public ?SeoData $seo = null,
    ) {}
}
