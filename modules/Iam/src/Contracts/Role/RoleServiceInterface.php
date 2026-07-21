<?php

declare(strict_types=1);

namespace Commerce\Iam\Contracts\Role;

use Commerce\Iam\DTO\CreateRoleData;
use Commerce\Iam\DTO\UpdateRoleData;
use Commerce\Iam\Models\Role;

interface RoleServiceInterface
{
    public function create(CreateRoleData $data): Role;

    public function update(string $uuid, UpdateRoleData $data): Role;

    public function delete(string $uuid): void;

    public function findByUuid(string $uuid): ?Role;
}
