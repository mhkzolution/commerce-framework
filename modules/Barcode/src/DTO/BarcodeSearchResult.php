<?php

declare(strict_types=1);

namespace Commerce\Barcode\DTO;

use JsonSerializable;

final readonly class BarcodeSearchResult implements JsonSerializable
{
    public function __construct(
        public ?string $productUuid,
        public string $variantUuid,
        public string $sku,
        public string $productName,
        public string $variantName,
        public string $ownerName,
        public ?string $thumbnailUrl,
    ) {}

    /**
     * @return array{
     *     product_uuid: string|null,
     *     variant_uuid: string,
     *     sku: string,
     *     product_name: string,
     *     variant_name: string,
     *     owner_name: string,
     *     thumbnail_url: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'product_uuid' => $this->productUuid,
            'variant_uuid' => $this->variantUuid,
            'sku' => $this->sku,
            'product_name' => $this->productName,
            'variant_name' => $this->variantName,
            'owner_name' => $this->ownerName,
            'thumbnail_url' => $this->thumbnailUrl,
        ];
    }

    /**
     * @return array{
     *     product_uuid: string|null,
     *     variant_uuid: string,
     *     sku: string,
     *     product_name: string,
     *     variant_name: string,
     *     owner_name: string,
     *     thumbnail_url: string|null
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
