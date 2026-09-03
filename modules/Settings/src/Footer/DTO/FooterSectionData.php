<?php

declare(strict_types=1);

namespace Commerce\Settings\Footer\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class FooterSectionData extends DataTransferObject
{
    /**
     * @param  list<FooterLinkData>  $links
     */
    public function __construct(
        public string $id,
        public string $type,
        public ?string $title,
        public string $ariaLabel,
        public ?FooterBrandData $brand = null,
        public array $links = [],
        public ?string $text = null,
    ) {}
}
