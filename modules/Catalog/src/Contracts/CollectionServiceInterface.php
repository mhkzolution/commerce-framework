<?php

declare(strict_types=1);

namespace Commerce\Catalog\Contracts;

use Commerce\Catalog\DTO\CreateCollectionData;
use Commerce\Catalog\DTO\UpdateCollectionData;
use Commerce\Catalog\Models\Collection;

interface CollectionServiceInterface
{
    public function create(CreateCollectionData $data): Collection;

    public function update(string $uuid, UpdateCollectionData $data): Collection;

    public function delete(string $uuid): void;
}
