<?php

declare(strict_types=1);

namespace Commerce\Settings\Services;

use Commerce\Contracts\Settings\SettingRegistryServiceInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Settings\DTO\RegisterSettingData;
use Commerce\Settings\Models\Setting;
use Commerce\Settings\Models\SettingGroup;
use Commerce\Settings\Support\SettingValueCaster;
use Illuminate\Support\Str;

final class SettingRegistryService extends BaseService implements SettingRegistryServiceInterface
{
    /** @var list<array{key: string, schema: array<string, mixed>}> */
    private array $registry = [];

    public function register(string $key, array $schema): void
    {
        $this->registry[] = ['key' => $key, 'schema' => $schema];

        $parts = explode('.', $key, 2);
        $groupCode = $schema['group'] ?? ($parts[0] ?? 'general');
        $settingKey = $parts[1] ?? $key;
        $module = $schema['module'] ?? 'settings';
        $type = $schema['type'] ?? 'string';
        $default = $schema['default'] ?? null;

        $group = SettingGroup::query()->updateOrCreate(
            ['code' => $groupCode],
            [
                'module' => $module,
                'label' => Str::headline($groupCode),
                'position' => $schema['position'] ?? 0,
            ],
        );

        Setting::query()->updateOrCreate(
            [
                'tenant_id' => null,
                'group_id' => $group->id,
                'key' => $settingKey,
            ],
            [
                'type' => $type,
                'default_value' => SettingValueCaster::serialize($default, $type),
                'value' => SettingValueCaster::serialize($default, $type),
                'validation' => $schema['validation'] ?? null,
                'is_public' => (bool) ($schema['is_public'] ?? false),
                'meta' => ['label' => $schema['label'] ?? Str::headline($settingKey)],
            ],
        );
    }

    public function registerData(RegisterSettingData $data): void
    {
        $this->register($data->key, [
            'type' => $data->type,
            'label' => $data->label,
            'group' => $data->group,
            'default' => $data->default,
            'is_public' => $data->isPublic,
            'validation' => $data->validation,
            'module' => $data->module,
        ]);
    }

    public function all(): array
    {
        return $this->registry;
    }
}
