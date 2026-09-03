<?php

declare(strict_types=1);

namespace Commerce\Settings\Footer\Drivers;

use Commerce\Settings\Footer\Contracts\FooterSectionDriver;
use Commerce\Settings\Footer\DTO\FooterSection;
use Commerce\Settings\Footer\DTO\FooterSectionConfig;
use Throwable;

final class SocialSectionDriver implements FooterSectionDriver
{
    public function build(FooterSectionConfig $config): ?FooterSection
    {
        try {
            if (! $config->enabled) {
                return null;
            }

            return null;
        } catch (Throwable) {
            return null;
        }
    }

    public function supportsMultiple(): bool
    {
        return false;
    }
}
