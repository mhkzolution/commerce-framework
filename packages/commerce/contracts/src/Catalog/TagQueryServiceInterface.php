<?php

declare(strict_types=1);

namespace Commerce\Contracts\Catalog;

interface TagQueryServiceInterface
{
    public function findByUuid(string $uuid): ?object;

    public function findBySlug(string $slug): ?object;

    /**
     * @param  list<string>  $slugs
     * @return array<string, object>
     */
    public function findBySlugs(array $slugs): array;
}
