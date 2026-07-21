<?php

declare(strict_types=1);

namespace Commerce\Core\Base;

use Commerce\Contracts\Repository\RepositoryInterface;

abstract class BaseRepository implements RepositoryInterface
{
    public function findByUuid(string $uuid): ?object
    {
        return null;
    }

    public function findById(int $id): ?object
    {
        return null;
    }
}
