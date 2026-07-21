<?php

declare(strict_types=1);

namespace Commerce\Iam\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class LoginCredentialsData extends DataTransferObject
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $remember = false,
    ) {}
}
