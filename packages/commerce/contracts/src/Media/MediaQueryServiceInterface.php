<?php

declare(strict_types=1);

namespace Commerce\Contracts\Media;

interface MediaQueryServiceInterface
{
    public function findByUuid(string $uuid): ?object;

    public function getUrl(string $uuid, ?string $variant = null): ?string;

    public function getSrcset(string $uuid): ?string;

    /**
     * @param  list<string>  $uuids
     * @return array<string, object>
     */
    public function findByUuids(array $uuids): array;
}
