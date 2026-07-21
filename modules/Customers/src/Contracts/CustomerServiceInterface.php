<?php

declare(strict_types=1);

namespace Commerce\Customers\Contracts;

use Commerce\Customers\DTO\CreateCustomerData;
use Commerce\Customers\DTO\UpdateCustomerData;
use Commerce\Customers\Models\Customer;

interface CustomerServiceInterface
{
    public function create(CreateCustomerData $data): Customer;

    public function update(string $uuid, UpdateCustomerData $data): Customer;

    public function delete(string $uuid): void;
}
