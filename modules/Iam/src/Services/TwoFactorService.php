<?php

declare(strict_types=1);

namespace Commerce\Iam\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Iam\Contracts\TwoFactor\TwoFactorServiceInterface;
use Commerce\Iam\Models\User;
use Commerce\Iam\Support\TotpGenerator;
use Illuminate\Support\Facades\Crypt;

final class TwoFactorService extends BaseService implements TwoFactorServiceInterface
{
    public function __construct(private readonly TotpGenerator $totp) {}

    public function isEnabled(User $user): bool
    {
        return $user->two_factor_confirmed_at !== null;
    }

    public function isRequired(): bool
    {
        return (bool) config('iam.two_factor.required', false);
    }

    public function enable(User $user): array
    {
        $secret = $this->totp->generateSecret();

        $user->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        return [
            'secret' => $secret,
            'qr_code_url' => $this->totp->getQrCodeUrl(
                (string) config('app.name', 'Commerce'),
                $user->email,
                $secret,
            ),
        ];
    }

    public function confirm(User $user, string $code): bool
    {
        $secret = $this->resolveSecret($user);

        if ($secret === null || ! $this->totp->verify($secret, $code)) {
            return false;
        }

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($this->generatePlainRecoveryCodes(), JSON_THROW_ON_ERROR)),
        ])->save();

        return true;
    }

    public function verify(User $user, string $code): bool
    {
        if (! $this->isEnabled($user)) {
            return true;
        }

        $secret = $this->resolveSecret($user);

        if ($secret !== null && $this->totp->verify($secret, $code)) {
            return true;
        }

        return $this->verifyRecoveryCode($user, $code);
    }

    public function disable(User $user, string $code): bool
    {
        if (! $this->verify($user, $code)) {
            return false;
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return true;
    }

    public function generateRecoveryCodes(User $user): array
    {
        $codes = $this->generatePlainRecoveryCodes();

        $user->forceFill([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($codes, JSON_THROW_ON_ERROR)),
        ])->save();

        return $codes;
    }

    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $codes = $this->resolveRecoveryCodes($user);
        $normalized = strtoupper(trim($code));

        if (! in_array($normalized, $codes, true)) {
            return false;
        }

        $remaining = array_values(array_filter(
            $codes,
            static fn (string $existing): bool => $existing !== $normalized,
        ));

        $user->forceFill([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($remaining, JSON_THROW_ON_ERROR)),
        ])->save();

        return true;
    }

    private function resolveSecret(User $user): ?string
    {
        if ($user->two_factor_secret === null) {
            return null;
        }

        try {
            return Crypt::decryptString($user->two_factor_secret);
        } catch (\Throwable) {
            throw new DomainException('Two-factor secret is invalid.');
        }
    }

    /**
     * @return list<string>
     */
    private function resolveRecoveryCodes(User $user): array
    {
        if ($user->two_factor_recovery_codes === null) {
            return [];
        }

        try {
            $decoded = json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? array_values(array_map('strval', $decoded)) : [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<string>
     */
    private function generatePlainRecoveryCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }

        return $codes;
    }
}
