<?php

declare(strict_types=1);

namespace Commerce\Contracts\Customer;

interface CustomerQueryServiceInterface
{
    public function findByUuid(string $uuid): ?object;

    public function findByEmail(string $email): ?object;
}
