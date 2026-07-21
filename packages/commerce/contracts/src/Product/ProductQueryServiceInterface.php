<?php

declare(strict_types=1);

namespace Commerce\Contracts\Product;

interface ProductQueryServiceInterface
{
    public function findByUuid(string $uuid): ?object;

    public function findBySlug(string $slug): ?object;

    public function findVariantByUuid(string $uuid): ?object;
}
