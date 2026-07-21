<?php

declare(strict_types=1);

namespace Commerce\Iam\Contracts\User;

use Commerce\Iam\DTO\CreateUserData;
use Commerce\Iam\DTO\UpdateUserData;
use Commerce\Iam\Models\User;

interface UserServiceInterface
{
    public function create(CreateUserData $data): User;

    public function update(string $uuid, UpdateUserData $data): User;

    public function delete(string $uuid, ?int $actorId = null): void;

    public function findByUuid(string $uuid): ?User;

    public function findByEmail(string $email): ?User;
}
