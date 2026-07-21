<?php

declare(strict_types=1);

namespace Commerce\Settings\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class UpdateSettingsGroupData extends DataTransferObject
{
    /**
     * @param  array<string, mixed>  $values  keyed by setting key (without group prefix)
     */
    public function __construct(
        public string $group,
        public array $values,
    ) {}
}
