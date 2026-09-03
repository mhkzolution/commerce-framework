<?php

declare(strict_types=1);

namespace Commerce\Settings\Services;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Contracts\Settings\WebsiteSettingsQueryServiceInterface;
use Commerce\Contracts\Settings\WebsiteSocialLinkData;
use Throwable;

final class WebsiteSettingsQueryService implements WebsiteSettingsQueryServiceInterface
{
    /**
     * @var array<string, string>
     */
    private const NETWORKS = [
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
        'line' => 'LINE',
    ];

    public function __construct(
        private readonly SettingQueryServiceInterface $settings,
    ) {}

    /**
     * @return list<WebsiteSocialLinkData>
     */
    public function socialLinks(): array
    {
        try {
            $links = [];

            foreach (self::NETWORKS as $key => $label) {
                $url = $this->settings->get('social.'.$key);

                if (! is_string($url)) {
                    continue;
                }

                $trimmed = trim($url);
                if ($trimmed === '') {
                    continue;
                }

                $links[] = new WebsiteSocialLinkData(
                    key: $key,
                    label: $label,
                    url: $trimmed,
                );
            }

            return $links;
        } catch (Throwable) {
            return [];
        }
    }
}
