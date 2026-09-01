<?php

declare(strict_types=1);

namespace Commerce\Settings\Services;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Core\Base\BaseQueryService;
use Commerce\Settings\Models\Setting;
use Commerce\Settings\Models\SettingGroup;
use Commerce\Settings\Support\SettingTenantScope;
use Commerce\Settings\Support\SettingValueCaster;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class SettingQueryService extends BaseQueryService implements SettingQueryServiceInterface
{
    /** @var list<string> */
    private const DEDICATED_UI_GROUPS = ['site', 'theme', 'mail', 'auth', 'customer_experience'];

    public function __construct(
        private readonly SettingTenantScope $tenantScope,
    ) {}

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

        foreach ($this->tenantScope->apply($groupModel->settings())->orderBy('key')->get() as $setting) {
            $values[$setting->key] = $this->get("{$group}.{$setting->key}");
        }

        return $values;
    }

    /**
     * @return list<array{group: SettingGroup, settings: Collection<int, Setting>}>
     */
    public function getAdminStructure(): array
    {
        return SettingGroup::query()
            ->whereNotIn('code', self::DEDICATED_UI_GROUPS)
            ->with(['settings' => fn ($query) => $this->tenantScope->apply($query)->orderBy('key')])
            ->orderBy('position')
            ->orderBy('label')
            ->get()
            ->map(static fn (SettingGroup $group): array => [
                'group' => $group,
                'settings' => $group->settings,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function getPublicSettings(): array
    {
        $values = [];

        $this->tenantScope
            ->apply(Setting::query())
            ->where('is_public', true)
            ->with('group')
            ->orderBy('group_id')
            ->orderBy('key')
            ->get()
            ->each(function (Setting $setting) use (&$values): void {
                $groupCode = $setting->group?->code;

                if ($groupCode === null) {
                    return;
                }

                $fullKey = "{$groupCode}.{$setting->key}";
                $raw = $setting->value ?? $setting->default_value;

                $values[$fullKey] = $raw === null
                    ? null
                    : SettingValueCaster::cast($raw, $setting->type);
            });

        return $values;
    }

    public function clearCache(?string $key = null): void
    {
        if ($key !== null) {
            Cache::forget($this->cacheKey($key));

            return;
        }

        $this->tenantScope
            ->apply(Setting::query())
            ->with('group')
            ->each(function (Setting $setting): void {
                Cache::forget($this->cacheKey($setting->full_key));
            });
    }

    private function findSetting(string $key): ?Setting
    {
        [$groupCode, $settingKey] = array_pad(explode('.', $key, 2), 2, null);

        if ($settingKey === null) {
            return null;
        }

        return $this->tenantScope
            ->apply(Setting::query())
            ->whereHas('group', static fn ($query) => $query->where('code', $groupCode))
            ->where('key', $settingKey)
            ->first();
    }

    private function cacheKey(string $key): string
    {
        return $this->tenantScope->cachePrefix().$key;
    }
}
