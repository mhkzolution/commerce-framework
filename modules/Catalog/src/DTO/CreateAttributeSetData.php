<?php

declare(strict_types=1);

namespace Commerce\Catalog\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class CreateAttributeSetData extends DataTransferObject
{
    /**
     * @param  list<int>  $attributeIds
     */
    public function __construct(
        public string $code,
        public string $name,
        public array $attributeIds = [],
    ) {}
}
