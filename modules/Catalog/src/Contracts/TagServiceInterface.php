<?php

declare(strict_types=1);

namespace Commerce\Catalog\Contracts;

use Commerce\Catalog\DTO\CreateTagData;
use Commerce\Catalog\Models\Tag;

interface TagServiceInterface
{
    public function create(CreateTagData $data): Tag;

    public function delete(string $uuid): void;
}
