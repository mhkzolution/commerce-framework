<?php

declare(strict_types=1);

namespace Commerce\Iam\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class UpdateUserData extends DataTransferObject
{
    public function __construct(
        public string $name,
        public string $email,
        public string $status = 'active',
        public ?string $password = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        /** @var list<string>|null */
        public ?array $roleCodes = null,
    ) {}
}
