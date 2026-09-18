<?php

declare(strict_types=1);

namespace Commerce\Contracts\Storefront;

final readonly class StorefrontAccessContext
{
    /**
     * @var list<string>
     */
    private const PRICE_KEYS = [
        'price',
        'sale_price',
        'compare_at_price',
        'compareAtPrice',
        'formatted_price',
        'formatted_sale_price',
        'list_price',
    ];

    public function __construct(
        public StoreVisibility $mode,
        public bool $authenticated,
        public bool $entitled,
        public bool $canViewCatalog,
        public bool $canViewPrices,
        public bool $canPurchase,
    ) {}

    public function robots(?string $pageRobots = null): string
    {
        if ($this->mode === StoreVisibility::Members || $this->mode === StoreVisibility::Private) {
            return 'noindex,nofollow';
        }

        return is_string($pageRobots) && $pageRobots !== '' ? $pageRobots : 'index,follow';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function withoutPrices(array $payload): array
    {
        if ($this->canViewPrices) {
            return $payload;
        }

        foreach (self::PRICE_KEYS as $key) {
            if (array_key_exists($key, $payload)) {
                $payload[$key] = null;
            }
        }

        $payload['prices_hidden'] = true;

        return $payload;
    }
}
