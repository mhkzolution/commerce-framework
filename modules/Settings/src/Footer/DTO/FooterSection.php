<?php

declare(strict_types=1);

namespace Commerce\Settings\Footer\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class FooterSection extends DataTransferObject
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $id,
        public string $type,
        public ?string $titleKey = null,
        public array $items = [],
        public array $meta = [],
    ) {}
}
