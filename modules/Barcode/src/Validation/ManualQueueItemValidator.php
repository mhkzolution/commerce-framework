<?php

declare(strict_types=1);

namespace Commerce\Barcode\Validation;

use Commerce\Barcode\DTO\BarcodeQueueItemData;
use Illuminate\Validation\ValidationException;

final class ManualQueueItemValidator
{
    public function __construct(
        private readonly BarcodeQueueItemValidator $baseValidator,
    ) {}

    /**
     * @param  list<BarcodeQueueItemData>  $queue
     *
     * @throws ValidationException
     */
    public function validate(BarcodeQueueItemData $item, array $queue = []): void
    {
        $this->baseValidator->validate($item);
    }
}
