<?php

declare(strict_types=1);

namespace Commerce\Contracts\Repository;

interface RepositoryInterface
{
    public function findByUuid(string $uuid): ?object;

    public function findById(int $id): ?object;
}
