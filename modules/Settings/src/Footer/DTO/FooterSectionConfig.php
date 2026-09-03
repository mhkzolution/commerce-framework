<?php

declare(strict_types=1);

namespace Commerce\Settings\Footer\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class FooterSectionConfig extends DataTransferObject
{
    /**
     * @param  array<string, mixed>  $visibility
     * @param  array<string, mixed>  $settings
     */
    public function __construct(
        public string $id,
        public string $type,
        public bool $enabled = true,
        public array $visibility = [],
        public array $settings = [],
        public ?FooterBuildContext $context = null,
    ) {}
}
