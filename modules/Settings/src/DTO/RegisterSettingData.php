<?php

declare(strict_types=1);

namespace Commerce\Settings\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class RegisterSettingData extends DataTransferObject
{
    /**
     * @param  array<string, mixed>  $validation
     */
    public function __construct(
        public string $key,
        public string $type,
        public string $label,
        public string $group,
        public mixed $default = null,
        public bool $isPublic = false,
        public array $validation = [],
        public string $module = 'settings',
    ) {}
}
