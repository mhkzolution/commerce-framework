<?php

declare(strict_types=1);

namespace Commerce\Settings\Support;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Illuminate\Support\Facades\Config;

final class MailConfigurator
{
    public static function apply(): void
    {
        if (! app()->bound(SettingQueryServiceInterface::class)) {
            return;
        }

        try {
            $settings = app(SettingQueryServiceInterface::class)->getGroup('mail');
        } catch (\Throwable) {
            return;
        }

        $mailer = self::string($settings['mailer'] ?? null);
        if ($mailer !== '') {
            Config::set('mail.default', $mailer);
        }

        $host = self::string($settings['host'] ?? null);
        if ($host !== '') {
            Config::set('mail.mailers.smtp.host', $host);
        }

        $port = self::string($settings['port'] ?? null);
        if ($port !== '') {
            Config::set('mail.mailers.smtp.port', (int) $port);
        }

        $username = self::string($settings['username'] ?? null);
        if ($username !== '') {
            Config::set('mail.mailers.smtp.username', $username);
        }

        $password = self::string($settings['password'] ?? null);
        if ($password !== '') {
            Config::set('mail.mailers.smtp.password', $password);
        }

        $encryption = self::string($settings['encryption'] ?? null);
        Config::set('mail.mailers.smtp.encryption', $encryption !== '' ? $encryption : null);

        $fromAddress = self::string($settings['from_address'] ?? null);
        if ($fromAddress !== '') {
            Config::set('mail.from.address', $fromAddress);
        }

        $fromName = self::string($settings['from_name'] ?? null);
        if ($fromName !== '') {
            Config::set('mail.from.name', $fromName);
        }
    }

    private static function string(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }
}
