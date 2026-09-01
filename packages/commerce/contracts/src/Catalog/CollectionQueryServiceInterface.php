<?php

declare(strict_types=1);

namespace Commerce\Contracts\Catalog;

interface CollectionQueryServiceInterface
{
    public function findByUuid(string $uuid): ?object;

    public function findBySlug(string $slug): ?object;
}
