<?php

declare(strict_types=1);

namespace Commerce\Contracts\Settings;

interface WebsiteSettingsQueryServiceInterface
{
    /**
     * @return list<WebsiteSocialLinkData>
     */
    public function socialLinks(): array;
}
