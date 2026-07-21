<?php

declare(strict_types=1);

namespace Commerce\Customers\Contracts;

use Commerce\Customers\DTO\RegisterCustomerData;
use Commerce\Customers\Models\Customer;

interface CustomerAuthServiceInterface
{
    public function register(RegisterCustomerData $data): Customer;

    public function attempt(string $email, string $password, bool $remember = false): bool;

    public function logout(): void;

    public function current(): ?Customer;
}
