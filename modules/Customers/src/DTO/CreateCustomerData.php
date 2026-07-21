<?php

declare(strict_types=1);

namespace Commerce\Customers\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class CreateCustomerData extends DataTransferObject
{
    public function __construct(
        public string $email,
        public string $name,
        public ?string $phone = null,
        public string $status = 'active',
    ) {}
}
