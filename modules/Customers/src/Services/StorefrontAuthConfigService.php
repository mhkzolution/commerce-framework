<?php

declare(strict_types=1);

namespace Commerce\Customers\Services;

use Commerce\Contracts\Settings\SiteIdentityServiceInterface;
use Illuminate\Support\Facades\Route;

final class StorefrontAuthConfigService
{
    /**
     * @return list<string>
     */
    public function loginModes(): array
    {
        $modes = config('customers.storefront.login_modes', ['email', 'phone', 'otp']);

        if (! is_array($modes)) {
            return ['email'];
        }

        $modes = array_values($modes);

        if (! $this->otpEnabled()) {
            $modes = array_values(array_filter($modes, static fn (string $mode): bool => $mode !== 'otp'));
        }

        return $modes !== [] ? $modes : ['email'];
    }

    public function registrationEnabled(): bool
    {
        return (bool) config('customers.storefront.registration.enabled', true);
    }

    public function otpEnabled(): bool
    {
        return (bool) config('customers.storefront.otp.enabled', false);
    }

    public function recaptchaEnabled(): bool
    {
        return app(RecaptchaVerifier::class)->enabled();
    }

    public function forgotPasswordEnabled(): bool
    {
        return (bool) config('customers.storefront.forgot_password.enabled', true);
    }

    /**
     * @return list<array{key: string, label: string, route: string|null, enabled: bool}>
     */
    public function oauthProviders(): array
    {
        $configured = config('customers.storefront.oauth', []);
        $providers = [];

        foreach (['google', 'line', 'apple'] as $key) {
            $settings = is_array($configured[$key] ?? null) ? $configured[$key] : [];
            $enabled = (bool) ($settings['enabled'] ?? false);

            if ($key === 'line') {
                $enabled = $enabled
                    && is_string($settings['channel_id'] ?? null) && trim($settings['channel_id']) !== ''
                    && is_string($settings['channel_secret'] ?? null) && trim($settings['channel_secret']) !== '';
            }

            $providers[] = [
                'key' => $key,
                'label' => $key,
                'enabled' => $enabled && Route::has('storefront.account.oauth.redirect'),
            ];
        }

        return array_values(array_filter($providers, static fn (array $provider): bool => $provider['enabled']));
    }

    /**
     * @return array{email: ?string, phone: ?string}
     */
    public function support(): array
    {
        if (app()->bound(SiteIdentityServiceInterface::class)) {
            $site = app(SiteIdentityServiceInterface::class);

            return [
                'email' => $site->contactEmail(),
                'phone' => $site->contactPhone(),
            ];
        }

        $support = config('customers.storefront.support', []);

        return [
            'email' => is_string($support['email'] ?? null) && $support['email'] !== '' ? $support['email'] : null,
            'phone' => is_string($support['phone'] ?? null) && $support['phone'] !== '' ? $support['phone'] : null,
        ];
    }
}
