<?php

declare(strict_types=1);

namespace Commerce\Settings\Footer\Contracts;

use Commerce\Settings\Footer\DTO\FooterSection;
use Commerce\Settings\Footer\DTO\FooterSectionConfig;

interface FooterSectionDriver
{
    public function build(FooterSectionConfig $config): ?FooterSection;

    public function supportsMultiple(): bool;
}
