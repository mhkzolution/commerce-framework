<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use Commerce\Contracts\Settings\WebsiteBrandData;
use Commerce\Contracts\Settings\WebsiteContactData;
use Commerce\Contracts\Settings\WebsiteSeoDefaultsData;
use Commerce\Contracts\Settings\WebsiteSettingsQueryServiceInterface;
use Commerce\Contracts\Settings\WebsiteSocialLinkData;
use Commerce\Settings\Services\FooterSocialQuery;
use RuntimeException;
use Tests\TestCase;

final class FooterSocialQueryTest extends TestCase
{
    public function test_links_are_empty_when_website_settings_are_unbound(): void
    {
        $this->assertSame([], (new FooterSocialQuery)->links());
    }

    public function test_links_map_social_dtos_to_driver_arrays(): void
    {
        $query = new FooterSocialQuery(new class implements WebsiteSettingsQueryServiceInterface
        {
            public function brand(): WebsiteBrandData
            {
                return new WebsiteBrandData('', null, null);
            }

            public function socialLinks(): array
            {
                return [
                    new WebsiteSocialLinkData('facebook', 'Facebook', 'https://facebook.com/harbor'),
                ];
            }

            public function contact(): WebsiteContactData
            {
                return new WebsiteContactData(null, null);
            }

            public function seoDefaults(): WebsiteSeoDefaultsData
            {
                return new WebsiteSeoDefaultsData(null, null, null);
            }
        });

        $this->assertSame([
            [
                'label' => 'Facebook',
                'url' => 'https://facebook.com/harbor',
                'key' => 'facebook',
            ],
        ], $query->links());
    }

    public function test_links_fail_soft_when_website_settings_throw(): void
    {
        $query = new FooterSocialQuery(new class implements WebsiteSettingsQueryServiceInterface
        {
            public function brand(): WebsiteBrandData
            {
                throw new RuntimeException('unavailable');
            }

            public function socialLinks(): array
            {
                throw new RuntimeException('unavailable');
            }

            public function contact(): WebsiteContactData
            {
                throw new RuntimeException('unavailable');
            }

            public function seoDefaults(): WebsiteSeoDefaultsData
            {
                throw new RuntimeException('unavailable');
            }
        });

        $this->assertSame([], $query->links());
    }
}
