<?php

declare(strict_types=1);

namespace Commerce\Catalog\Contracts;

use Commerce\Catalog\DTO\CreateAttributeData;
use Commerce\Catalog\DTO\UpdateAttributeData;
use Commerce\Catalog\Models\Attribute;

interface AttributeServiceInterface
{
    public function create(CreateAttributeData $data): Attribute;

    public function update(string $uuid, UpdateAttributeData $data): Attribute;

    public function delete(string $uuid): void;
}
