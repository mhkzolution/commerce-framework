<?php

declare(strict_types=1);

namespace Commerce\Contracts\Authorization;

interface AuthorizationServiceInterface
{
    public function can(?object $user, string $permission, mixed $resource = null): bool;

    public function hasRole(?object $user, string $roleCode): bool;

    /**
     * @return list<string>
     */
    public function getPermissionsForUser(int $userId): array;
}
