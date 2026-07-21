<?php

declare(strict_types=1);

namespace Commerce\Catalog\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class UpdateAttributeData extends DataTransferObject
{
    /**
     * @param  list<string>|null  $options
     */
    public function __construct(
        public string $code,
        public string $name,
        public string $type = 'text',
        public bool $isFilterable = false,
        public bool $isRequired = false,
        public bool $isVisible = true,
        public int $position = 0,
        public ?array $options = null,
    ) {}
}
