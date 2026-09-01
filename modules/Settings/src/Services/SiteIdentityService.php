<?php

declare(strict_types=1);

namespace Commerce\Settings\Services;

use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Contracts\Settings\SiteIdentityServiceInterface;
use Commerce\Core\Base\BaseService;

final class SiteIdentityService extends BaseService implements SiteIdentityServiceInterface
{
    public function __construct(
        private readonly SettingQueryServiceInterface $settings,
    ) {}

    public function name(): string
    {
        foreach (['site.name', 'store.name'] as $key) {
            $value = $this->stringSetting($key);

            if ($value !== null) {
                return $value;
            }
        }

        return (string) config('commerce.name', 'Commerce Framework');
    }

    public function logoUrl(?string $variant = null): ?string
    {
        return $this->mediaUrl('site.logo_media_uuid', $variant);
    }

    public function faviconUrl(): ?string
    {
        return $this->mediaUrl('site.favicon_media_uuid');
    }

    public function contactAddress(): ?string
    {
        return $this->nullableString('site.contact_address');
    }

    public function contactEmail(): ?string
    {
        return $this->nullableString('site.contact_email')
            ?? $this->nullableString('store.email');
    }

    public function contactPhone(): ?string
    {
        return $this->nullableString('site.contact_phone');
    }

    public function socialLinks(): array
    {
        $links = [];

        foreach ($this->socialDefinitions() as $key => $label) {
            $raw = $this->nullableString("site.social_{$key}");

            if ($raw === null) {
                continue;
            }

            $url = $this->normalizeSocialUrl($key, $raw);

            if ($url === null) {
                continue;
            }

            $links[] = [
                'key' => $key,
                'label' => $label,
                'url' => $url,
            ];
        }

        return $links;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name(),
            'logo_url' => $this->logoUrl(),
            'favicon_url' => $this->faviconUrl(),
            'contact_address' => $this->contactAddress(),
            'contact_email' => $this->contactEmail(),
            'contact_phone' => $this->contactPhone(),
            'social' => $this->socialLinks(),
        ];
    }

    private function stringSetting(string $key): ?string
    {
        if (! $this->settings->has($key)) {
            return null;
        }

        $value = $this->settings->get($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function nullableString(string $key): ?string
    {
        if (! $this->settings->has($key)) {
            return null;
        }

        $value = $this->settings->get($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function mediaUrl(string $settingKey, ?string $variant = null): ?string
    {
        $uuid = $this->nullableString($settingKey);

        if ($uuid === null) {
            return null;
        }

        $media = $this->media();

        if ($media === null) {
            return null;
        }

        return $media->getUrl($uuid, $variant) ?? $media->getUrl($uuid);
    }

    private function media(): ?MediaQueryServiceInterface
    {
        return app()->bound(MediaQueryServiceInterface::class)
            ? app(MediaQueryServiceInterface::class)
            : null;
    }

    /**
     * @return array<string, string>
     */
    private function socialDefinitions(): array
    {
        return [
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'tiktok' => 'TikTok',
            'line' => 'LINE',
        ];
    }

    private function normalizeSocialUrl(string $key, string $value): ?string
    {
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        return match ($key) {
            'line' => str_starts_with($value, '@')
                ? 'https://line.me/R/ti/p/'.rawurlencode($value)
                : (str_contains($value, 'line.me') ? 'https://'.ltrim($value, '/') : $value),
            default => null,
        };
    }
}
