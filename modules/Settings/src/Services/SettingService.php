<?php

declare(strict_types=1);

namespace Commerce\Settings\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\DTO\UpdateSettingsGroupData;
use Commerce\Settings\Models\Setting;
use Commerce\Settings\Models\SettingGroup;
use Commerce\Settings\Support\SettingValueCaster;
use Commerce\Contracts\Event\EventBusInterface;
use Commerce\Settings\Events\SettingsGroupUpdated;

final class SettingService extends BaseService implements SettingServiceInterface
{
    public function __construct(
        private readonly SettingQueryService $queryService,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function updateGroup(UpdateSettingsGroupData $data): void
    {
        $group = SettingGroup::query()->where('code', $data->group)->firstOrFail();

        foreach ($data->values as $key => $value) {
            $setting = Setting::query()
                ->where('group_id', $group->id)
                ->where('key', $key)
                ->whereNull('tenant_id')
                ->first();

            if ($setting === null) {
                continue;
            }

            $setting->update([
                'value' => SettingValueCaster::serialize($value, $setting->type),
            ]);

            $this->queryService->clearCache("{$data->group}.{$key}");
        }

        $this->eventBus->dispatch(new SettingsGroupUpdated(
            group: $data->group,
            keys: array_keys($data->values),
        ));
    }

    public function resetGroup(string $groupCode): void
    {
        $group = SettingGroup::query()->where('code', $groupCode)->firstOrFail();

        foreach ($group->settings as $setting) {
            $setting->update(['value' => $setting->default_value]);
            $this->queryService->clearCache("{$groupCode}.{$setting->key}");
        }

        $this->eventBus->dispatch(new SettingsGroupUpdated(
            group: $groupCode,
            keys: $group->settings->pluck('key')->all(),
        ));
    }
}
