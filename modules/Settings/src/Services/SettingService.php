<?php

declare(strict_types=1);

namespace Commerce\Settings\Services;

use Commerce\Contracts\Event\EventBusInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\DTO\UpdateSettingsGroupData;
use Commerce\Settings\Events\SettingsGroupUpdated;
use Commerce\Settings\Models\Setting;
use Commerce\Settings\Models\SettingGroup;
use Commerce\Settings\Support\SettingTenantScope;
use Commerce\Settings\Support\SettingValueCaster;

final class SettingService extends BaseService implements SettingServiceInterface
{
    public function __construct(
        private readonly SettingQueryService $queryService,
        private readonly SettingTenantScope $tenantScope,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function updateGroup(UpdateSettingsGroupData $data): void
    {
        $group = SettingGroup::query()->where('code', $data->group)->firstOrFail();

        foreach ($data->values as $key => $value) {
            $setting = $this->tenantScope
                ->apply(Setting::query())
                ->where('group_id', $group->id)
                ->where('key', $key)
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
            tenantId: $this->tenantScope->scopedTenantId(),
        ));
    }

    public function resetGroup(string $groupCode): void
    {
        $group = SettingGroup::query()->where('code', $groupCode)->firstOrFail();

        $settings = $this->tenantScope
            ->apply($group->settings())
            ->get();

        foreach ($settings as $setting) {
            $setting->update(['value' => $setting->default_value]);
            $this->queryService->clearCache("{$groupCode}.{$setting->key}");
        }

        $this->eventBus->dispatch(new SettingsGroupUpdated(
            group: $groupCode,
            keys: $settings->pluck('key')->all(),
            tenantId: $this->tenantScope->scopedTenantId(),
        ));
    }
}
