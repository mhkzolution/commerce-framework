<?php

declare(strict_types=1);

namespace Commerce\Contracts\Storefront;

final readonly class HeaderViewData
{
    /**
     * @param  array{promo: array{enabled: bool, message: string, dismissible: bool}, items: list<array<string, mixed>>}  $primaryNav
     */
    public function __construct(
        public HeaderBrandData $brand,
        public HeaderNavigationData $navigation,
        public HeaderActionData $actions,
        public array $primaryNav = [
            'promo' => ['enabled' => false, 'message' => '', 'dismissible' => true],
            'items' => [],
        ],
    ) {}
}
