<?php

declare(strict_types=1);

namespace Commerce\Settings\Http\Controllers\Admin;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Contracts\Settings\SettingRegistryServiceInterface;
use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\DTO\UpdateSettingsGroupData;
use Commerce\Settings\Http\Requests\UpdateAppearanceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class AppearanceController extends Controller
{
    /**
     * @var array<string, array{token: string, label_key: string, default: string}>
     */
    private const COLOR_FIELDS = [
        'primary' => ['token' => 'primary', 'label_key' => 'appearance_color_primary', 'default' => '#2563eb'],
        'primary_hover' => ['token' => 'primary-hover', 'label_key' => 'appearance_color_primary_hover', 'default' => '#1d4ed8'],
        'primary_active' => ['token' => 'primary-active', 'label_key' => 'appearance_color_primary_active', 'default' => '#1e40af'],
        'accent' => ['token' => 'accent', 'label_key' => 'appearance_color_accent', 'default' => '#2563eb'],
        'accent_hover' => ['token' => 'accent-hover', 'label_key' => 'appearance_color_accent_hover', 'default' => '#1d4ed8'],
        'background' => ['token' => 'background', 'label_key' => 'appearance_color_background', 'default' => '#f4f6f8'],
        'surface' => ['token' => 'surface', 'label_key' => 'appearance_color_surface', 'default' => '#ffffff'],
        'money' => ['token' => 'money', 'label_key' => 'appearance_color_money', 'default' => '#dc2626'],
    ];

    public function __construct(
        private readonly SettingQueryServiceInterface $settingQueryService,
        private readonly SettingServiceInterface $settingService,
        private readonly SettingRegistryServiceInterface $registry,
    ) {}

    public function show(): View
    {
        $this->ensureRegisteredKeys();

        $settings = $this->settingQueryService->getGroup('theme');
        $colors = [];

        foreach (self::COLOR_FIELDS as $key => $meta) {
            $value = $settings[$key] ?? '';
            $colors[$key] = [
                'label' => __("settings::admin.{$meta['label_key']}"),
                'token' => $meta['token'],
                'default' => $meta['default'],
                'value' => is_string($value) && $value !== '' ? $value : $meta['default'],
            ];
        }

        return view('settings::admin.appearance.index', [
            'colors' => $colors,
        ]);
    }

    public function update(UpdateAppearanceRequest $request): RedirectResponse
    {
        $this->ensureRegisteredKeys();

        $values = [];

        foreach (array_keys(self::COLOR_FIELDS) as $key) {
            $value = $request->validated($key);
            $values[$key] = $value === '' ? null : $value;
        }

        $this->settingService->updateGroup(new UpdateSettingsGroupData(
            group: 'theme',
            values: $values,
        ));

        return redirect()
            ->route('admin.settings.appearance.show')
            ->with('status', __('settings::admin.appearance_saved'));
    }

    private function ensureRegisteredKeys(): void
    {
        foreach (self::COLOR_FIELDS as $key => $meta) {
            $fullKey = "theme.{$key}";

            if ($this->settingQueryService->has($fullKey)) {
                continue;
            }

            $this->registry->register($fullKey, [
                'type' => 'string',
                'label' => $meta['label_key'],
                'group' => 'theme',
                'default' => $meta['default'],
                'is_public' => true,
                'module' => 'settings',
            ]);
        }
    }
}
