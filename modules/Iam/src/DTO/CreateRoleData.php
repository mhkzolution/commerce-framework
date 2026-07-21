<?php

declare(strict_types=1);

namespace Commerce\Iam\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class CreateRoleData extends DataTransferObject
{
    public function __construct(
        public string $name,
        public string $code,
        public ?string $description = null,
        /** @var list<string> */
        public array $permissionNames = [],
    ) {}
}
