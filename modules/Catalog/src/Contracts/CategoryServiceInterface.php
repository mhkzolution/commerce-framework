<?php

declare(strict_types=1);

namespace Commerce\Catalog\Contracts;

use Commerce\Catalog\DTO\CreateCategoryData;
use Commerce\Catalog\DTO\UpdateCategoryData;
use Commerce\Catalog\Models\Category;

interface CategoryServiceInterface
{
    public function create(CreateCategoryData $data): Category;

    public function update(string $uuid, UpdateCategoryData $data): Category;

    public function delete(string $uuid): void;

    public function reorder(string $uuid, int $position): Category;
}
