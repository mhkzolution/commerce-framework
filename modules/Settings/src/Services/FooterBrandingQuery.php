<?php

declare(strict_types=1);

namespace Commerce\Settings\Services;

use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Settings\Footer\DTO\FooterBrandData;
use Throwable;

final class FooterBrandingQuery
{
    public function __construct(
        private readonly SettingQueryServiceInterface $settings,
    ) {}

    public function current(): FooterBrandData
    {
        return new FooterBrandData(
            displayName: $this->displayName(),
            logoUrl: $this->logoUrl(),
            description: $this->description(),
        );
    }

    private function displayName(): string
    {
        try {
            $storeName = $this->settings->get('store.name');
            if (is_string($storeName) && trim($storeName) !== '') {
                return trim($storeName);
            }
        } catch (Throwable) {
        }

        $appName = config('app.name');
        if (is_string($appName) && trim($appName) !== '') {
            return trim($appName);
        }

        return 'Commerce Framework';
    }

    private function logoUrl(): ?string
    {
        try {
            $uuid = $this->settings->get('store.logo_media_uuid');
        } catch (Throwable) {
            return null;
        }

        if (! is_string($uuid) || $uuid === '') {
            return null;
        }

        if (! app()->bound(MediaQueryServiceInterface::class)) {
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

    private function description(): ?string
    {
        try {
            foreach (['store.description', 'site.description'] as $key) {
                $value = $this->settings->get($key);

                if (is_string($value) && trim($value) !== '') {
                    return trim($value);
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }
}
