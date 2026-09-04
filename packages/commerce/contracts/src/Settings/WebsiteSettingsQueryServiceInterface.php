<?php

declare(strict_types=1);

namespace Commerce\Contracts\Settings;

interface WebsiteSettingsQueryServiceInterface
{
    public function brand(): WebsiteBrandData;

    /**
     * @return list<WebsiteSocialLinkData>
     */
    public function socialLinks(): array;

    public function contact(): WebsiteContactData;

    public function seoDefaults(): WebsiteSeoDefaultsData;
}
