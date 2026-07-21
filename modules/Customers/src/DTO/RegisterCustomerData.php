<?php

declare(strict_types=1);

namespace Commerce\Customers\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class RegisterCustomerData extends DataTransferObject
{
    public function __construct(
        public string $email,
        public string $name,
        public string $password,
        public ?string $phone = null,
    ) {}
}
