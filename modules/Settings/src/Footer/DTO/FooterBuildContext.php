<?php

declare(strict_types=1);

namespace Commerce\Settings\Footer\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class FooterBuildContext extends DataTransferObject
{
    /**
     * @param  array<string, bool>  $featureFlags
     * @param  array<string, bool>  $serviceAvailability
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public ?string $device = null,
        public ?string $planTier = null,
        public array $featureFlags = [],
        public array $serviceAvailability = [],
        public array $meta = [],
    ) {}
}
