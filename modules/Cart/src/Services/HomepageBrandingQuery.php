<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\DTO\HomepageBrandingData;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Throwable;

final class HomepageBrandingQuery
{
    public function __construct(
        private readonly SettingQueryServiceInterface $settings,
    ) {}

    public function current(): HomepageBrandingData
    {
        return new HomepageBrandingData(
            name: $this->name(),
            logoUrl: $this->logoUrl(),
        );
    }

    private function name(): string
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
}
