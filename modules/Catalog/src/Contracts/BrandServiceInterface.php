<?php

declare(strict_types=1);

namespace Commerce\Catalog\Contracts;

use Commerce\Catalog\DTO\CreateBrandData;
use Commerce\Catalog\DTO\UpdateBrandData;
use Commerce\Catalog\Models\Brand;

interface BrandServiceInterface
{
    public function create(CreateBrandData $data): Brand;

    public function update(string $uuid, UpdateBrandData $data): Brand;

    public function delete(string $uuid): void;
}
