<?php

declare(strict_types=1);

namespace Commerce\Orders\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class CreateShipmentData extends DataTransferObject
{
    /**
     * @param  array<string, int>  $quantitiesByLineUuid
     */
    public function __construct(
        public array $quantitiesByLineUuid,
        public ?string $carrier = null,
        public ?string $trackingNumber = null,
        public ?string $trackingUrl = null,
        public ?string $notes = null,
        public ?string $createdByUserUuid = null,
    ) {}
}
