<?php

declare(strict_types=1);

namespace Commerce\Settings\Footer\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class FooterPageData extends DataTransferObject
{
    /**
     * @param  list<FooterSectionData>  $sections
     */
    public function __construct(
        public bool $enabled,
        public string $className,
        public array $sections,
    ) {}
}
