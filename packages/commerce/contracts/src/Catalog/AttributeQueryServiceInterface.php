<?php

declare(strict_types=1);

namespace Commerce\Contracts\Catalog;

interface AttributeQueryServiceInterface
{
    public function findByUuid(string $uuid): ?object;

    public function findByCode(string $code): ?object;

    /**
     * @return list<object>
     */
    public function forAttributeSet(string $setUuid): array;
}
