<?php

declare(strict_types=1);

namespace Commerce\Settings\Services;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Core\Base\BaseQueryService;
use Commerce\Settings\Models\Setting;
use Commerce\Settings\Models\SettingGroup;
use Commerce\Settings\Support\SettingValueCaster;
use Illuminate\Support\Facades\Cache;

final class SettingQueryService extends BaseQueryService implements SettingQueryServiceInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            $this->cacheKey($key),
            now()->addSeconds((int) config('settings.cache_ttl', 3600)),
            function () use ($key, $default): mixed {
                $setting = $this->findSetting($key);

                if ($setting === null) {
                    return $default;
                }

                $raw = $setting->value ?? $setting->default_value;

                return $raw === null
                    ? $default
                    : SettingValueCaster::cast($raw, $setting->type);
            },
        );
    }

    public function has(string $key): bool
    {
        return $this->findSetting($key) !== null;
    }

    public function getGroup(string $group): array
    {
        $groupModel = SettingGroup::query()->where('code', $group)->first();

        if ($groupModel === null) {
            return [];
        }

        $values = [];

        foreach ($groupModel->settings()->orderBy('key')->get() as $setting) {
            $values[$setting->key] = $this->get("{$group}.{$setting->key}");
        }

        return $values;
    }

    /**
     * @return list<array{group: SettingGroup, settings: \Illuminate\Support\Collection<int, Setting>}>
     */
    public function getAdminStructure(): array
    {
        return SettingGroup::query()
            ->with(['settings' => static fn ($query) => $query->orderBy('key')])
            ->orderBy('position')
            ->orderBy('label')
            ->get()
            ->map(static fn (SettingGroup $group): array => [
                'group' => $group,
                'settings' => $group->settings,
            ])
            ->all();
    }

    public function clearCache(?string $key = null): void
    {
        if ($key !== null) {
            Cache::forget($this->cacheKey($key));

            return;
        }

        Setting::query()->each(function (Setting $setting): void {
            Cache::forget($this->cacheKey($setting->full_key));
        });
    }

    private function findSetting(string $key): ?Setting
    {
        [$groupCode, $settingKey] = array_pad(explode('.', $key, 2), 2, null);

        if ($settingKey === null) {
            return null;
        }

        return Setting::query()
            ->whereHas('group', static fn ($query) => $query->where('code', $groupCode))
            ->where('key', $settingKey)
            ->whereNull('tenant_id')
            ->first();
    }

    private function cacheKey(string $key): string
    {
        return 'settings.' . $key;
    }
}
