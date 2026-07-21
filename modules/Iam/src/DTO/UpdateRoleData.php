<?php

declare(strict_types=1);

namespace Commerce\Iam\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class UpdateRoleData extends DataTransferObject
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        /** @var list<string> */
        public array $permissionNames = [],
    ) {}
}
