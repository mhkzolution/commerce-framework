<?php

declare(strict_types=1);

namespace Commerce\Barcode\Adapters;

use Commerce\Barcode\DTO\BarcodeQueueItemData;
use Commerce\Barcode\Enums\BarcodeQueueSource;
use Commerce\Barcode\Services\BarcodeOwnerResolver;
use Commerce\Barcode\Support\BarcodeSkuNormalizer;

final class ManualQueueItemAdapter
{
    public function __construct(
        private readonly BarcodeOwnerResolver $ownerResolver,
    ) {}

    /**
     * @param  array{name: string, barcode: string, sku?: string, seller_uuid?: string|null, owner_name?: string}  $input
     */
    public function fromInput(array $input, int $quantity = 1): BarcodeQueueItemData
    {
        $barcode = BarcodeSkuNormalizer::normalize((string) $input['barcode']);
        $displayText = trim((string) ($input['sku'] ?? ''));
        $sellerUuid = isset($input['seller_uuid']) && $input['seller_uuid'] !== ''
            ? (string) $input['seller_uuid']
            : null;

        $ownerName = isset($input['owner_name']) && trim((string) $input['owner_name']) !== ''
            ? trim((string) $input['owner_name'])
            : $this->ownerResolver->resolveForSeller($sellerUuid);

        return new BarcodeQueueItemData(
            source: BarcodeQueueSource::Manual,
            title: trim((string) $input['name']),
            barcode: $barcode,
            displayText: $displayText !== '' ? $displayText : $barcode,
            ownerName: $ownerName,
            quantity: max(1, $quantity),
            meta: $sellerUuid ? ['seller_uuid' => $sellerUuid] : [],
        );
    }
}
