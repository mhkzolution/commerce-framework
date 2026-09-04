<?php

declare(strict_types=1);

namespace Commerce\Settings\Support;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Illuminate\Support\Facades\Config;

final class AuthConfigurator
{
    public static function apply(): void
    {
        if (! app()->bound(SettingQueryServiceInterface::class)) {
            return;
        }

        try {
            $settings = app(SettingQueryServiceInterface::class)->getGroup('auth');
        } catch (\Throwable) {
            return;
        }

        if ($settings === []) {
            return;
        }

        if (array_key_exists('recaptcha_enabled', $settings)) {
            Config::set('customers.storefront.recaptcha.enabled', (bool) $settings['recaptcha_enabled']);
        }

        if (array_key_exists('recaptcha_site_key', $settings)) {
            Config::set('customers.storefront.recaptcha.site_key', self::string($settings['recaptcha_site_key']));
        }

        if (array_key_exists('recaptcha_secret_key', $settings)) {
            Config::set('customers.storefront.recaptcha.secret_key', self::string($settings['recaptcha_secret_key']));
        }

        if (array_key_exists('recaptcha_min_score', $settings) && $settings['recaptcha_min_score'] !== null && $settings['recaptcha_min_score'] !== '') {
            Config::set('customers.storefront.recaptcha.min_score', (float) $settings['recaptcha_min_score']);
        }

        if (array_key_exists('line_enabled', $settings)) {
            Config::set('customers.storefront.oauth.line.enabled', (bool) $settings['line_enabled']);
        }

        if (array_key_exists('line_channel_id', $settings)) {
            Config::set('customers.storefront.oauth.line.channel_id', self::string($settings['line_channel_id']));
        }

        if (array_key_exists('line_channel_secret', $settings)) {
            Config::set('customers.storefront.oauth.line.channel_secret', self::string($settings['line_channel_secret']));
        }

        if (array_key_exists('registration_enabled', $settings)) {
            Config::set('customers.storefront.registration.enabled', (bool) $settings['registration_enabled']);
        }
    }

    private static function string(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }
}
