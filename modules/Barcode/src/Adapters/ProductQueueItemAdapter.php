<?php

declare(strict_types=1);

namespace Commerce\Barcode\Adapters;

use Commerce\Barcode\DTO\BarcodeQueueItemData;
use Commerce\Barcode\Enums\BarcodeQueueSource;
use Commerce\Barcode\Support\BarcodeSkuNormalizer;

final class ProductQueueItemAdapter
{
    /**
     * @param  array{
     *     variant_uuid: string,
     *     thumbnail_url: string|null,
     *     owner_name: string,
     *     product_name: string,
     *     variant_name: string,
     *     sku: string
     * }  $searchResult
     */
    public function fromSearchResult(array $searchResult, int $quantity = 1): BarcodeQueueItemData
    {
        $barcode = BarcodeSkuNormalizer::normalize((string) $searchResult['sku']);

        return new BarcodeQueueItemData(
            source: BarcodeQueueSource::Product,
            title: (string) $searchResult['product_name'],
            barcode: $barcode,
            displayText: $barcode,
            ownerName: (string) $searchResult['owner_name'],
            quantity: max(1, $quantity),
            thumbnailUrl: $searchResult['thumbnail_url'] ?? null,
            variantId: (string) $searchResult['variant_uuid'],
            meta: [
                'variant_name' => (string) ($searchResult['variant_name'] ?? ''),
            ],
        );
    }
}
