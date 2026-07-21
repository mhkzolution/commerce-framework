<?php

declare(strict_types=1);

namespace Commerce\Settings\Contracts;

use Commerce\Settings\DTO\UpdateSettingsGroupData;

interface SettingServiceInterface
{
    public function updateGroup(UpdateSettingsGroupData $data): void;

    public function resetGroup(string $groupCode): void;
}
