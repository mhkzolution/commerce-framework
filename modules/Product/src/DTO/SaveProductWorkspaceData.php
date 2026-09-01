<?php

declare(strict_types=1);

namespace Commerce\Product\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class SaveProductWorkspaceData extends DataTransferObject
{
    /**
     * @param  list<int>  $categoryIds
     * @param  list<int>  $collectionIds
     * @param  list<int>  $tagIds
     * @param  list<string>  $mediaUuids
     * @param  array<int, mixed>  $attributeValues
     * @param  list<array<string, mixed>>  $variantOptions
     * @param  list<array<string, mixed>>  $variants
     * @param  array<string, mixed>  $meta
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
        public ?string $publishAt = null,
        public array $categoryIds = [],
        public array $collectionIds = [],
        public array $tagIds = [],
        public array $mediaUuids = [],
        public array $attributeValues = [],
        public ?SeoData $seo = null,
        public array $variantOptions = [],
        public array $variants = [],
        public ?string $skuPattern = null,
        public array $meta = [],
    ) {}
}
