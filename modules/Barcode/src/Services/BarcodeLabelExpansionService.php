<?php

declare(strict_types=1);

namespace Commerce\Barcode\Services;

use Commerce\Barcode\DTO\BarcodeQueueItemData;

final class BarcodeLabelExpansionService
{
    public function __construct(
        private readonly BarcodeQueueItemNormalizer $normalizer,
        private readonly ExpandedLabelMapper $mapper,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return list<array{owner_name: string, barcode: string, display_text: string}>
     */
    public function expand(array $lines): array
    {
        $items = array_map(
            fn (array $line): BarcodeQueueItemData => $this->normalizer->normalize($line),
            $lines,
        );

        return array_map(
            static fn ($label) => $label->toArray(),
            $this->mapper->expandMany($items),
        );
    }
}
