<?php

declare(strict_types=1);

namespace Commerce\Barcode\Services;

use Commerce\Barcode\DTO\BarcodeQueueItemData;
use Commerce\Barcode\Enums\BarcodeQueueSource;
use Commerce\Barcode\Support\BarcodeSkuNormalizer;

final class BarcodeQueueItemNormalizer
{
    public function __construct(
        private readonly BarcodeOwnerResolver $ownerResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function normalize(array $payload): BarcodeQueueItemData
    {
        if (isset($payload['source'], $payload['barcode'], $payload['title'])) {
            return $this->fromCanonical($payload);
        }

        return $this->fromLegacy($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function fromCanonical(array $payload): BarcodeQueueItemData
    {
        $barcode = BarcodeSkuNormalizer::normalize((string) $payload['barcode']);
        $displayText = trim((string) ($payload['display_text'] ?? $barcode));
        $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
        $sellerUuid = isset($meta['seller_uuid']) ? (string) $meta['seller_uuid'] : null;
        $ownerName = trim((string) ($payload['owner_name'] ?? ''));

        if ($ownerName === '' && isset($payload['source']) && $payload['source'] === BarcodeQueueSource::Manual->value) {
            $ownerName = $this->ownerResolver->resolveForSeller($sellerUuid);
        }

        return new BarcodeQueueItemData(
            source: BarcodeQueueSource::from((string) $payload['source']),
            title: trim((string) $payload['title']),
            barcode: $barcode,
            displayText: $displayText !== '' ? $displayText : $barcode,
            ownerName: $ownerName,
            quantity: max(1, (int) ($payload['quantity'] ?? 1)),
            thumbnailUrl: isset($payload['thumbnail_url']) ? (string) $payload['thumbnail_url'] : null,
            variantId: isset($payload['variant_id']) ? (string) $payload['variant_id'] : null,
            productId: isset($payload['product_id']) ? (string) $payload['product_id'] : null,
            meta: is_array($payload['meta'] ?? null) ? $payload['meta'] : [],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function fromLegacy(array $payload): BarcodeQueueItemData
    {
        $variantId = $payload['variant_uuid'] ?? $payload['variant_id'] ?? null;
        $barcode = BarcodeSkuNormalizer::normalize((string) ($payload['barcode'] ?? $payload['sku'] ?? ''));
        $variantName = trim((string) ($payload['variant_name'] ?? ''));
        $source = filled($variantId) ? BarcodeQueueSource::Product : BarcodeQueueSource::Manual;

        $displayText = $barcode;
        if ($source === BarcodeQueueSource::Manual && $variantName !== '') {
            $displayText = $variantName;
        }

        return new BarcodeQueueItemData(
            source: $source,
            title: trim((string) ($payload['title'] ?? $payload['product_name'] ?? '')),
            barcode: $barcode,
            displayText: $displayText,
            ownerName: trim((string) ($payload['owner_name'] ?? '')),
            quantity: max(1, (int) ($payload['quantity'] ?? 1)),
            thumbnailUrl: isset($payload['thumbnail_url']) ? (string) $payload['thumbnail_url'] : null,
            variantId: filled($variantId) ? (string) $variantId : null,
            productId: isset($payload['product_id']) ? (string) $payload['product_id'] : null,
            meta: is_array($payload['meta'] ?? null) ? $payload['meta'] : [],
        );
    }
}
