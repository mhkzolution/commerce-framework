<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Commerce\Contracts\Storefront\StoreVisibility;
use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\DTO\UpdateSettingsGroupData;
use Commerce\Settings\Services\StoreVisibilityConfig;

trait SetsStoreVisibility
{
    protected function setStoreVisibility(StoreVisibility|string $mode): void
    {
        $value = $mode instanceof StoreVisibility ? $mode->value : $mode;

        app(StoreVisibilityConfig::class)->ensureRegistered();
        app(SettingServiceInterface::class)->updateGroup(new UpdateSettingsGroupData(
            group: 'store',
            values: ['visibility' => $value],
        ));
    }
}
