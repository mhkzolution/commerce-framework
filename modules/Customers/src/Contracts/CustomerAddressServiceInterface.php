<?php

declare(strict_types=1);

namespace Commerce\Customers\Contracts;

use Commerce\Customers\DTO\CreateAddressData;
use Commerce\Customers\Models\CustomerAddress;

interface CustomerAddressServiceInterface
{
    public function create(CreateAddressData $data): CustomerAddress;

    public function delete(string $uuid): void;

    public function setDefault(string $uuid): CustomerAddress;
}
