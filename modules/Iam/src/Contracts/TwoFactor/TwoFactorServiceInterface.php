<?php

declare(strict_types=1);

namespace Commerce\Iam\Contracts\TwoFactor;

use Commerce\Iam\Models\User;

interface TwoFactorServiceInterface
{
    public function isEnabled(User $user): bool;

    public function isRequired(): bool;

    /**
     * @return array{secret: string, qr_code_url: string}
     */
    public function enable(User $user): array;

    public function confirm(User $user, string $code): bool;

    public function verify(User $user, string $code): bool;

    public function disable(User $user, string $code): bool;

    /**
     * @return list<string>
     */
    public function generateRecoveryCodes(User $user): array;

    public function verifyRecoveryCode(User $user, string $code): bool;
}
