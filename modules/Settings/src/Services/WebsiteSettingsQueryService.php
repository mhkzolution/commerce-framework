<?php

declare(strict_types=1);

namespace Commerce\Settings\Services;

use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Contracts\Settings\WebsiteBrandData;
use Commerce\Contracts\Settings\WebsiteContactData;
use Commerce\Contracts\Settings\WebsiteSeoDefaultsData;
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

    public function brand(): WebsiteBrandData
    {
        try {
            return new WebsiteBrandData(
                name: $this->stringValue('store.name') ?? '',
                logoUrl: $this->mediaUrl($this->stringValue('store.logo_media_uuid')),
                description: $this->stringValue('store.description'),
            );
        } catch (Throwable) {
            return new WebsiteBrandData(name: '', logoUrl: null, description: null);
        }
    }

    /**
     * @return list<WebsiteSocialLinkData>
     */
    public function socialLinks(): array
    {
        try {
            $links = [];

            foreach (self::NETWORKS as $key => $label) {
                $url = $this->stringValue('social.'.$key);

                if ($url === null) {
                    continue;
                }

                $links[] = new WebsiteSocialLinkData(
                    key: $key,
                    label: $label,
                    url: $url,
                );
            }

            return $links;
        } catch (Throwable) {
            return [];
        }
    }

    public function contact(): WebsiteContactData
    {
        try {
            return new WebsiteContactData(
                email: $this->stringValue('store.email'),
                phone: $this->stringValue('store.phone'),
            );
        } catch (Throwable) {
            return new WebsiteContactData(email: null, phone: null);
        }
    }

    public function seoDefaults(): WebsiteSeoDefaultsData
    {
        try {
            return new WebsiteSeoDefaultsData(
                titleSuffix: $this->stringValue('website.seo.title_suffix'),
                defaultDescription: $this->stringValue('website.seo.default_description'),
                defaultOgImageUrl: $this->mediaUrl($this->stringValue('website.seo.default_og_image_media_uuid')),
            );
        } catch (Throwable) {
            return new WebsiteSeoDefaultsData(titleSuffix: null, defaultDescription: null, defaultOgImageUrl: null);
        }
    }

    private function stringValue(string $key): ?string
    {
        $value = $this->settings->get($key);

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function mediaUrl(?string $uuid): ?string
    {
        if ($uuid === null || ! app()->bound(MediaQueryServiceInterface::class)) {
            return null;
        }

        try {
            $media = app(MediaQueryServiceInterface::class);

            return $media->getUrl($uuid, 'large')
                ?? $media->getUrl($uuid, 'medium')
                ?? $media->getUrl($uuid);
        } catch (Throwable) {
            return null;
        }
    }
}
