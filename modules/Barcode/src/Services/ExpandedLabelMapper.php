<?php

declare(strict_types=1);

namespace Commerce\Barcode\Services;

use Commerce\Barcode\DTO\BarcodeQueueItemData;
use Commerce\Barcode\DTO\ExpandedLabelData;

final class ExpandedLabelMapper
{
    public function fromQueueItem(BarcodeQueueItemData $item): ExpandedLabelData
    {
        return new ExpandedLabelData(
            ownerName: $item->ownerName,
            barcode: $item->barcode,
            displayText: $item->displayText,
        );
    }

    /**
     * @return list<ExpandedLabelData>
     */
    public function expand(BarcodeQueueItemData $item): array
    {
        $label = $this->fromQueueItem($item);
        $labels = [];

        for ($i = 0; $i < $item->quantity; $i++) {
            $labels[] = $label;
        }

        return $labels;
    }

    /**
     * @param  list<BarcodeQueueItemData>  $items
     * @return list<ExpandedLabelData>
     */
    public function expandMany(array $items): array
    {
        $labels = [];

        foreach ($items as $item) {
            array_push($labels, ...$this->expand($item));
        }

        return $labels;
    }
}
