<?php

declare(strict_types=1);

namespace Commerce\Settings\Services;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Contracts\Settings\SettingRegistryServiceInterface;
use Commerce\Contracts\Storefront\StoreVisibility;
use Commerce\Core\Base\BaseService;
use Throwable;

final class StoreVisibilityConfig extends BaseService
{
    public const SETTING_KEY = 'store.visibility';

    public function __construct(
        private readonly SettingQueryServiceInterface $settings,
        private readonly SettingRegistryServiceInterface $registry,
    ) {}

    public function mode(): StoreVisibility
    {
        try {
            $stored = $this->settings->get(self::SETTING_KEY, StoreVisibility::Public->value);
        } catch (Throwable) {
            return StoreVisibility::Public;
        }

        return StoreVisibility::tryFrom(is_string($stored) ? $stored : '') ?? StoreVisibility::Public;
    }

    public function ensureRegistered(): void
    {
        try {
            if ($this->settings->has(self::SETTING_KEY)) {
                return;
            }
        } catch (Throwable) {
            return;
        }

        $this->registry->register(self::SETTING_KEY, [
            'type' => 'string',
            'label' => 'Store visibility',
            'group' => 'store',
            'default' => StoreVisibility::Public->value,
            'is_public' => true,
            'module' => 'settings',
            'validation' => ['required', 'in:'.implode(',', StoreVisibility::values())],
        ]);
    }
}
