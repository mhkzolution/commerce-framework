<?php

declare(strict_types=1);

namespace Commerce\Contracts\Seo;

interface SeoServiceInterface
{
    public function getForEntity(string $entityType, string $entityUuid): ?object;

    /**
     * @param  array<string, mixed>  $data
     */
    public function setForEntity(string $entityType, string $entityUuid, array $data): void;

    public function deleteForEntity(string $entityType, string $entityUuid): void;
}
