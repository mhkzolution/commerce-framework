<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Throwable;

final class ProductFallbackImageQuery
{
    private bool $resolved = false;

    private ?string $uuid = null;

    public function __construct(
        private readonly SettingQueryServiceInterface $settings,
        private readonly MediaQueryServiceInterface $media,
    ) {}

    public function uuid(): ?string
    {
        if ($this->resolved) {
            return $this->uuid;
        }

        $this->resolved = true;

        try {
            $value = $this->settings->get('product.fallback_image_media_uuid');
        } catch (Throwable) {
            return null;
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        $this->uuid = $value;

        return $this->uuid;
    }

    public function url(?string $variant = 'card'): ?string
    {
        $uuid = $this->uuid();

        if ($uuid === null) {
            return null;
        }

        try {
            if ($variant !== null) {
                $url = $this->media->getUrl($uuid, $variant);

                if (is_string($url) && $url !== '') {
                    return $url;
                }
            }

            $url = $this->media->getUrl($uuid);

            return is_string($url) && $url !== '' ? $url : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function srcset(): ?string
    {
        $uuid = $this->uuid();

        if ($uuid === null) {
            return null;
        }

        try {
            $srcset = $this->media->getSrcset($uuid);

            return is_string($srcset) && $srcset !== '' ? $srcset : null;
        } catch (Throwable) {
            return null;
        }
    }
}
