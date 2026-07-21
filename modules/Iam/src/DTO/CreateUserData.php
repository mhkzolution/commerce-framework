<?php

declare(strict_types=1);

namespace Commerce\Iam\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class CreateUserData extends DataTransferObject
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $status = 'active',
        public ?string $firstName = null,
        public ?string $lastName = null,
        /** @var list<string> */
        public array $roleCodes = [],
    ) {}
}
