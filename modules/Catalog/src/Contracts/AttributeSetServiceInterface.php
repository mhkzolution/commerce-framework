<?php

declare(strict_types=1);

namespace Commerce\Catalog\Contracts;

use Commerce\Catalog\DTO\CreateAttributeSetData;
use Commerce\Catalog\DTO\UpdateAttributeSetData;
use Commerce\Catalog\Models\AttributeSet;

interface AttributeSetServiceInterface
{
    public function create(CreateAttributeSetData $data): AttributeSet;

    public function update(string $uuid, UpdateAttributeSetData $data): AttributeSet;

    public function delete(string $uuid): void;
}
