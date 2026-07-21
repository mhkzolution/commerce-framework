<?php

declare(strict_types=1);

namespace Commerce\Contracts\Seo;

interface SlugServiceInterface
{
    public function generate(string $source, string $entityType, ?string $tenantScope = null): string;

    public function isAvailable(string $slug, string $entityType, ?string $tenantScope = null): bool;

    public function register(string $slug, string $entityType, string $entityUuid, ?int $tenantId = null): void;

    public function unregister(string $entityType, string $entityUuid): void;
}
