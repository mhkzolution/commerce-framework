<?php

declare(strict_types=1);

namespace Commerce\Settings\Services;

use Commerce\Contracts\Navigation\NavigationQueryServiceInterface;
use Throwable;

final class FooterNavigationQuery
{
    public function __construct(
        private readonly ?NavigationQueryServiceInterface $navigation = null,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function links(string $source): array
    {
        if ($this->navigation === null) {
            return [];
        }

        try {
            $mapped = [];

            foreach ($this->navigation->links($source) as $link) {
                $mapped[] = [
                    'label' => $link->label,
                    'url' => $link->url,
                    'key' => $link->key,
                    'footer_enabled' => $link->footerEnabled,
                ];
            }

            return $mapped;
        } catch (Throwable) {
            return [];
        }
    }
}
