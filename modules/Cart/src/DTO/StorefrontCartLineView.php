<?php

declare(strict_types=1);

namespace Commerce\Cart\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class StorefrontCartLineView extends DataTransferObject
{
    public function __construct(
        public ResolvedCartLineData $line,
        public string $productUuid,
        public string $productSlug,
        public ?string $imageUrl,
        public ?string $variantLabel,
        public ?string $deliverySummary,
    ) {}
}
