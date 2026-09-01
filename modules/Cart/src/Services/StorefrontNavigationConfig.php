<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;

final class StorefrontNavigationConfig
{
    /**
     * @return array<string, mixed>
     */
    public function resolve(): array
    {
        $config = config('cart.storefront.primary_navigation', []);

        if (! app()->bound(SettingQueryServiceInterface::class)) {
            return $config;
        }

        $settings = app(SettingQueryServiceInterface::class);
        $promoBar = $config['promo_bar'] ?? [];

        $promoEnabled = $settings->get('storefront.navigation.promo_enabled');
        if ($promoEnabled !== null) {
            $promoBar['enabled'] = filter_var($promoEnabled, FILTER_VALIDATE_BOOLEAN);
        }

        $promoMessage = $settings->get('storefront.navigation.promo_message');
        if (is_string($promoMessage)) {
            $promoBar['message'] = $promoMessage;
        }

        $promoDismissible = $settings->get('storefront.navigation.promo_dismissible');
        if ($promoDismissible !== null) {
            $promoBar['dismissible'] = filter_var($promoDismissible, FILTER_VALIDATE_BOOLEAN);
        }

        $itemsJson = $settings->get('storefront.navigation.items_json');
        if (is_string($itemsJson) && trim($itemsJson) !== '') {
            $decoded = json_decode($itemsJson, true);
            if (is_array($decoded)) {
                $config['items'] = $decoded;
            }
        }

        $config['promo_bar'] = $promoBar;

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return config('cart.storefront.primary_navigation', []);
    }
}
