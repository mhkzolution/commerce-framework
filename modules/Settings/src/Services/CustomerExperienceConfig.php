<?php

declare(strict_types=1);

namespace Commerce\Settings\Services;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Contracts\Settings\SettingRegistryServiceInterface;
use Commerce\Core\Base\BaseService;

final class CustomerExperienceConfig extends BaseService
{
    public const SETTING_KEY = 'customer_experience.config';

    public const SECTIONS = [
        'quickView',
        'notifications',
        'navigation',
        'productCard',
        'productDetail',
        'cart',
        'checkout',
    ];

    /** @var array<string, mixed>|null */
    private ?array $resolved = null;

    public function __construct(
        private readonly SettingQueryServiceInterface $settings,
        private readonly SettingRegistryServiceInterface $registry,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'quickView' => [
                'enabled' => true,
                'showImages' => true,
                'showName' => true,
                'showPrice' => true,
                'showSalePrice' => true,
                'showPromotionBadge' => true,
                'showShortDescription' => true,
                'showFullDescription' => false,
                'showStockStatus' => true,
                'showRemainingStock' => true,
                'showSku' => false,
                'showBrand' => true,
                'showCategory' => true,
                'showTags' => true,
                'showVariants' => true,
                'showQuantitySelector' => true,
                'showAddToCart' => true,
                'showBuyNow' => true,
                'showWishlist' => true,
                'showViewFullDetail' => true,
            ],
            'notifications' => [
                'enabled' => true,
                'duration' => 5,
                'position' => 'bottom-right',
                'newProduct' => true,
                'promotion' => true,
                'lowStock' => false,
                'review' => false,
                'recentPurchase' => false,
            ],
            'navigation' => [
                'backToTop' => true,
                'showAfter' => 500,
                'position' => 'bottom-right',
                'style' => 'circle',
                'fadeIn' => true,
                'smoothScroll' => true,
                'target' => 'top',
            ],
            'productCard' => [
                'enabled' => true,
            ],
            'productDetail' => [
                'enabled' => true,
            ],
            'cart' => [
                'enabled' => true,
            ],
            'checkout' => [
                'enabled' => true,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolve(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $stored = $this->settings->get(self::SETTING_KEY, []);

        if (! is_array($stored)) {
            $stored = [];
        }

        $this->resolved = $this->merge($stored);

        return $this->resolved;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function merge(array $overrides): array
    {
        $merged = $this->defaults();

        foreach (self::SECTIONS as $section) {
            $incoming = $overrides[$section] ?? null;

            if (! is_array($incoming)) {
                continue;
            }

            foreach ($merged[$section] as $key => $default) {
                if (! array_key_exists($key, $incoming)) {
                    continue;
                }

                $merged[$section][$key] = $this->castValue($incoming[$key], $default);
            }
        }

        return $merged;
    }

    public function ensureRegistered(): void
    {
        if ($this->settings->has(self::SETTING_KEY)) {
            return;
        }

        $this->registry->register(self::SETTING_KEY, [
            'type' => 'json',
            'label' => 'Customer experience',
            'group' => 'customer_experience',
            'default' => $this->defaults(),
            'is_public' => true,
            'module' => 'settings',
            'validation' => ['nullable', 'array'],
        ]);

        $this->resolved = null;
    }

    /**
     * @return array<string, mixed>
     */
    public function quickView(): array
    {
        return $this->resolve()['quickView'];
    }

    /**
     * @return array<string, mixed>
     */
    public function notifications(): array
    {
        return $this->resolve()['notifications'];
    }

    /**
     * @return array<string, mixed>
     */
    public function navigation(): array
    {
        return $this->resolve()['navigation'];
    }

    public function quickViewEnabled(): bool
    {
        return (bool) $this->quickView()['enabled'];
    }

    public function notificationsEnabled(): bool
    {
        return (bool) $this->notifications()['enabled'];
    }

    public function backToTopEnabled(): bool
    {
        return (bool) $this->navigation()['backToTop'];
    }

    /**
     * @return array<string, mixed>
     */
    public function previewCatalog(): array
    {
        return [
            'product' => [
                'id' => 'preview',
                'uuid' => 'preview',
                'name' => 'Nike Air Max',
                'slug' => 'nike-air-max',
                'url' => '#',
                'price' => 2990,
                'sale_price' => 2390,
                'compare_at_price' => 2990,
                'currency' => 'THB',
                'short_description' => 'Lightweight cushioning with a visible Air unit for all-day comfort.',
                'description' => 'Nike Air Max blends heritage running design with everyday wear. Mesh upper, padded collar, and a responsive Air sole.',
                'stock_status' => 'in_stock',
                'remaining_stock' => 12,
                'sku' => 'NK-AM-001',
                'brand' => 'Nike',
                'category' => 'Shoes',
                'tags' => ['Running', 'New'],
                'promotion_badge' => 'Sale 20%',
                'thumbnail' => null,
                'images' => [],
                'variants' => [
                    ['uuid' => 'size-40', 'name' => '40', 'available' => 4],
                    ['uuid' => 'size-41', 'name' => '41', 'available' => 5],
                    ['uuid' => 'size-42', 'name' => '42', 'available' => 3],
                ],
            ],
            'notifications' => [
                'newProduct' => [
                    'type' => 'newProduct',
                    'eyebrow' => 'New Product',
                    'title' => 'Nike Air Max',
                    'body' => '฿2,990',
                    'action' => 'View Product',
                ],
                'promotion' => [
                    'type' => 'promotion',
                    'eyebrow' => 'Sale 20%',
                    'title' => 'Nike Air Max',
                    'body' => 'Now ฿2,390',
                    'action' => null,
                ],
                'lowStock' => [
                    'type' => 'lowStock',
                    'eyebrow' => 'Low Stock',
                    'title' => 'Nike Air Max',
                    'body' => 'Only 3 left',
                    'action' => 'Shop now',
                ],
                'review' => [
                    'type' => 'review',
                    'eyebrow' => 'New Review',
                    'title' => 'Nike Air Max',
                    'body' => '★★★★★ “Love the cushioning”',
                    'action' => 'Read review',
                ],
                'recentPurchase' => [
                    'type' => 'recentPurchase',
                    'eyebrow' => 'Recent Purchase',
                    'title' => 'Someone in Bangkok bought',
                    'body' => 'Nike Air Max',
                    'action' => null,
                ],
            ],
        ];
    }

    private function castValue(mixed $value, mixed $default): mixed
    {
        if (is_bool($default)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if (is_int($default)) {
            return (int) $value;
        }

        if (is_string($default)) {
            return is_string($value) || is_numeric($value) ? (string) $value : $default;
        }

        return $value;
    }
}
