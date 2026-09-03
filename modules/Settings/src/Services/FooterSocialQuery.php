<?php

declare(strict_types=1);

namespace Commerce\Settings\Services;

use Commerce\Contracts\Settings\WebsiteSettingsQueryServiceInterface;
use Throwable;

final class FooterSocialQuery
{
    public function __construct(
        private readonly ?WebsiteSettingsQueryServiceInterface $website = null,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function links(): array
    {
        if ($this->website === null) {
            return [];
        }

        try {
            $mapped = [];

            foreach ($this->website->socialLinks() as $link) {
                $mapped[] = [
                    'label' => $link->label,
                    'url' => $link->url,
                    'key' => $link->key,
                ];
            }

            return $mapped;
        } catch (Throwable) {
            return [];
        }
    }
}
