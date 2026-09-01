<?php

declare(strict_types=1);

namespace Commerce\Settings\Http\Requests;

use Commerce\Settings\Models\SettingGroup;
use Commerce\Settings\Support\SettingTenantScope;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'settings' => ['required', 'array'],
        ];

        $groupCode = $this->route('group');

        if (! is_string($groupCode) || $groupCode === '') {
            return $rules;
        }

        $group = SettingGroup::query()->where('code', $groupCode)->first();

        if ($group === null) {
            return $rules;
        }

        $tenantScope = app(SettingTenantScope::class);

        foreach ($tenantScope->apply($group->settings())->get() as $setting) {
            $rules["settings.{$setting->key}"] = $setting->validation ?? ['nullable'];
        }

        return $rules;
    }
}
