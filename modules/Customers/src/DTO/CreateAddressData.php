<?php

declare(strict_types=1);

namespace Commerce\Customers\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class CreateAddressData extends DataTransferObject
{
    public function __construct(
        public string $customerUuid,
        public string $line1,
        public string $city,
        public string $postalCode,
        public string $countryCode,
        public string $type = 'shipping',
        public ?string $label = null,
        public ?string $line2 = null,
        public ?string $state = null,
        public ?string $district = null,
        public ?string $subdistrict = null,
        public bool $isDefault = false,
        public bool $isDefaultShipping = false,
        public bool $isDefaultBilling = false,
    ) {}
}
