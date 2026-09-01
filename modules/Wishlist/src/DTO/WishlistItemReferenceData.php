<?php

declare(strict_types=1);

namespace Commerce\Wishlist\DTO;

final readonly class WishlistItemReferenceData
{
    public function __construct(
        public string $productUuid,
        public ?string $variantUuid = null,
    ) {}

    /**
     * @param  array{product_id?: string, variant_id?: string|null}  $payload
     */
    public static function fromArray(array $payload): ?self
    {
        $productUuid = trim((string) ($payload['product_id'] ?? ''));

        if ($productUuid === '') {
            return null;
        }

        $variantUuid = $payload['variant_id'] ?? null;
        $variantUuid = is_string($variantUuid) && $variantUuid !== '' ? $variantUuid : null;

        return new self($productUuid, $variantUuid);
    }
}
