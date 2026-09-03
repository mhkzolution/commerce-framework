<?php

declare(strict_types=1);

namespace Commerce\Settings\Http\Controllers\Admin;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\DTO\UpdateSettingsGroupData;
use Commerce\Settings\Http\Requests\UpdateWebsiteSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class WebsiteSettingsController extends Controller
{
    public function __construct(
        private readonly SettingQueryServiceInterface $settings,
        private readonly SettingServiceInterface $settingService,
    ) {}

    public function show(): View
    {
        return view('settings::admin.website.index', [
            'name' => $this->stringValue('store.name'),
            'description' => $this->stringValue('store.description'),
            'logoMediaUuid' => $this->stringValue('store.logo_media_uuid'),
            'social' => [
                'facebook' => $this->stringValue('social.facebook'),
                'instagram' => $this->stringValue('social.instagram'),
                'tiktok' => $this->stringValue('social.tiktok'),
                'line' => $this->stringValue('social.line'),
            ],
        ]);
    }

    public function update(UpdateWebsiteSettingsRequest $request): RedirectResponse
    {
        $this->settingService->updateGroup(new UpdateSettingsGroupData(
            group: 'store',
            values: $request->storePayload(),
        ));

        $this->settingService->updateGroup(new UpdateSettingsGroupData(
            group: 'social',
            values: $request->socialPayload(),
        ));

        return redirect()
            ->route('admin.settings.website.show')
            ->with('status', __('settings::website.saved'));
    }

    private function stringValue(string $key): ?string
    {
        $value = $this->settings->get($key);

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
