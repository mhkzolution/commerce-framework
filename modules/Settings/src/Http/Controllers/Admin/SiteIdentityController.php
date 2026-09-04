<?php

declare(strict_types=1);

namespace Commerce\Settings\Http\Controllers\Admin;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\DTO\UpdateSettingsGroupData;
use Commerce\Settings\Http\Requests\UpdateSiteIdentityRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class SiteIdentityController extends Controller
{
    public function __construct(
        private readonly SettingQueryServiceInterface $settingQueryService,
        private readonly SettingServiceInterface $settingService,
    ) {}

    public function show(): View
    {
        $settings = $this->settingQueryService->getGroup('site');

        return view('settings::admin.site-identity.index', [
            'siteName' => $this->stringValue($settings['name'] ?? null)
                ?: $this->stringValue($this->settingQueryService->get('store.name')),
            'logoMediaUuid' => $this->nullableUuid($settings['logo_media_uuid'] ?? null)
                ?? $this->nullableUuid($this->settingQueryService->get('store.logo_media_uuid')),
            'faviconMediaUuid' => $this->nullableUuid($settings['favicon_media_uuid'] ?? null),
            'contactAddress' => $this->stringValue($settings['contact_address'] ?? null),
            'contactEmail' => $this->stringValue($settings['contact_email'] ?? null)
                ?: $this->stringValue($this->settingQueryService->get('store.email')),
            'contactPhone' => $this->stringValue($settings['contact_phone'] ?? null),
            'socialFacebook' => $this->stringValue($settings['social_facebook'] ?? null)
                ?: $this->stringValue($this->settingQueryService->get('social.facebook')),
            'socialInstagram' => $this->stringValue($settings['social_instagram'] ?? null)
                ?: $this->stringValue($this->settingQueryService->get('social.instagram')),
            'socialTiktok' => $this->stringValue($settings['social_tiktok'] ?? null)
                ?: $this->stringValue($this->settingQueryService->get('social.tiktok')),
            'socialLine' => $this->stringValue($settings['social_line'] ?? null)
                ?: $this->stringValue($this->settingQueryService->get('social.line')),
        ]);
    }

    public function update(UpdateSiteIdentityRequest $request): RedirectResponse
    {
        $this->settingService->updateGroup(new UpdateSettingsGroupData(
            group: 'site',
            values: [
                'name' => $request->validated('name'),
                'logo_media_uuid' => $request->validated('logo_media_uuid'),
                'favicon_media_uuid' => $request->validated('favicon_media_uuid'),
                'contact_address' => $request->validated('contact_address'),
                'contact_email' => $request->validated('contact_email'),
                'contact_phone' => $request->validated('contact_phone'),
                'social_facebook' => $request->validated('social_facebook'),
                'social_instagram' => $request->validated('social_instagram'),
                'social_tiktok' => $request->validated('social_tiktok'),
                'social_line' => $request->validated('social_line'),
            ],
        ));

        $this->settingService->updateGroup(new UpdateSettingsGroupData(
            group: 'store',
            values: [
                'name' => $request->validated('name'),
                'logo_media_uuid' => $request->validated('logo_media_uuid'),
            ],
        ));

        $this->settingService->updateGroup(new UpdateSettingsGroupData(
            group: 'social',
            values: [
                'facebook' => $request->validated('social_facebook'),
                'instagram' => $request->validated('social_instagram'),
                'tiktok' => $request->validated('social_tiktok'),
                'line' => $request->validated('social_line'),
            ],
        ));

        return redirect()
            ->route('admin.settings.site-identity.show')
            ->with('status', __('settings::admin.site_identity_saved'));
    }

    private function stringValue(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    private function nullableUuid(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
