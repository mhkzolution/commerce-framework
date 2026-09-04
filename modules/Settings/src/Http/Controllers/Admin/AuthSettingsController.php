<?php

declare(strict_types=1);

namespace Commerce\Settings\Http\Controllers\Admin;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\DTO\UpdateSettingsGroupData;
use Commerce\Settings\Http\Requests\UpdateAuthSettingsRequest;
use Commerce\Settings\Support\AuthConfigurator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

final class AuthSettingsController extends Controller
{
    public function __construct(
        private readonly SettingQueryServiceInterface $settingQueryService,
        private readonly SettingServiceInterface $settingService,
    ) {}

    public function show(): View
    {
        $settings = $this->settingQueryService->getGroup('auth');

        return view('settings::admin.auth.index', [
            'recaptchaEnabled' => (bool) ($settings['recaptcha_enabled'] ?? config('customers.storefront.recaptcha.enabled', false)),
            'recaptchaSiteKey' => (string) ($settings['recaptcha_site_key'] ?? config('customers.storefront.recaptcha.site_key', '')),
            'recaptchaSecretKey' => (string) ($settings['recaptcha_secret_key'] ?? config('customers.storefront.recaptcha.secret_key', '')),
            'recaptchaMinScore' => (string) ($settings['recaptcha_min_score'] ?? config('customers.storefront.recaptcha.min_score', 0.5)),
            'lineEnabled' => (bool) ($settings['line_enabled'] ?? config('customers.storefront.oauth.line.enabled', false)),
            'lineChannelId' => (string) ($settings['line_channel_id'] ?? config('customers.storefront.oauth.line.channel_id', '')),
            'lineChannelSecret' => (string) ($settings['line_channel_secret'] ?? config('customers.storefront.oauth.line.channel_secret', '')),
            'registrationEnabled' => (bool) ($settings['registration_enabled'] ?? config('customers.storefront.registration.enabled', true)),
            'lineCallbackUrl' => Route::has('storefront.account.oauth.callback')
                ? route('storefront.account.oauth.callback', ['provider' => 'line'])
                : url('/account/oauth/line/callback'),
        ]);
    }

    public function update(UpdateAuthSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $current = $this->settingQueryService->getGroup('auth');

        $values = [
            'recaptcha_enabled' => $validated['recaptcha_enabled'] ?? false,
            'recaptcha_site_key' => $validated['recaptcha_site_key'] ?? '',
            'recaptcha_min_score' => $validated['recaptcha_min_score'] ?? '0.5',
            'line_enabled' => $validated['line_enabled'] ?? false,
            'line_channel_id' => $validated['line_channel_id'] ?? '',
            'registration_enabled' => $validated['registration_enabled'] ?? true,
        ];

        if (($validated['recaptcha_secret_key'] ?? '') !== '') {
            $values['recaptcha_secret_key'] = $validated['recaptcha_secret_key'];
        } else {
            $values['recaptcha_secret_key'] = (string) ($current['recaptcha_secret_key'] ?? '');
        }

        if (($validated['line_channel_secret'] ?? '') !== '') {
            $values['line_channel_secret'] = $validated['line_channel_secret'];
        } else {
            $values['line_channel_secret'] = (string) ($current['line_channel_secret'] ?? '');
        }

        $this->settingService->updateGroup(new UpdateSettingsGroupData(
            group: 'auth',
            values: $values,
        ));

        AuthConfigurator::apply();

        return redirect()
            ->route('admin.settings.auth.show')
            ->with('status', __('settings::admin.auth_saved'));
    }
}
