<?php

declare(strict_types=1);

namespace Commerce\Settings\Http\Controllers\Admin;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Contracts\Settings\SettingRegistryServiceInterface;
use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\DTO\UpdateSettingsGroupData;
use Commerce\Settings\Http\Requests\UpdateWebsiteSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class WebsiteSettingsController extends Controller
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private const KEYS = [
        'store.phone' => ['type' => 'string', 'label' => 'Store Phone', 'group' => 'store', 'default' => null, 'is_public' => true],
        'website.seo.title_suffix' => ['type' => 'string', 'label' => 'SEO title suffix', 'group' => 'website', 'default' => null, 'is_public' => true],
        'website.seo.default_description' => ['type' => 'string', 'label' => 'SEO default description', 'group' => 'website', 'default' => null, 'is_public' => true],
        'website.seo.default_og_image_media_uuid' => ['type' => 'string', 'label' => 'SEO default OG image', 'group' => 'website', 'default' => null, 'is_public' => true],
    ];

    public function __construct(
        private readonly SettingQueryServiceInterface $settings,
        private readonly SettingServiceInterface $settingService,
        private readonly SettingRegistryServiceInterface $registry,
    ) {}

    public function show(): View
    {
        $this->ensureRegisteredKeys();

        return view('settings::admin.website.index', [
            'name' => $this->stringValue('store.name'),
            'description' => $this->stringValue('store.description'),
            'logoMediaUuid' => $this->stringValue('store.logo_media_uuid'),
            'email' => $this->stringValue('store.email'),
            'phone' => $this->stringValue('store.phone'),
            'social' => [
                'facebook' => $this->stringValue('social.facebook'),
                'instagram' => $this->stringValue('social.instagram'),
                'tiktok' => $this->stringValue('social.tiktok'),
                'line' => $this->stringValue('social.line'),
            ],
            'seoTitleSuffix' => $this->stringValue('website.seo.title_suffix'),
            'seoDefaultDescription' => $this->stringValue('website.seo.default_description'),
            'seoOgImageMediaUuid' => $this->stringValue('website.seo.default_og_image_media_uuid'),
        ]);
    }

    public function update(UpdateWebsiteSettingsRequest $request): RedirectResponse
    {
        $this->ensureRegisteredKeys();

        $this->settingService->updateGroup(new UpdateSettingsGroupData(
            group: 'store',
            values: $request->storePayload(),
        ));

        $this->settingService->updateGroup(new UpdateSettingsGroupData(
            group: 'social',
            values: $request->socialPayload(),
        ));

        $this->settingService->updateGroup(new UpdateSettingsGroupData(
            group: 'website',
            values: $request->websitePayload(),
        ));

        return redirect()
            ->route('admin.settings.website.show')
            ->with('status', __('settings::website.saved'));
    }

    private function ensureRegisteredKeys(): void
    {
        foreach (self::KEYS as $key => $schema) {
            if ($this->settings->has($key)) {
                continue;
            }

            $this->registry->register($key, array_merge($schema, ['module' => 'settings']));
        }
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
