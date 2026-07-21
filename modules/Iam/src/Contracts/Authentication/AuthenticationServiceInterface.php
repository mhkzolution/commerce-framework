<?php

declare(strict_types=1);

namespace Commerce\Iam\Contracts\Authentication;

use Commerce\Iam\DTO\LoginCredentialsData;
use Commerce\Iam\DTO\LoginResultData;
use Commerce\Iam\Models\User;

interface AuthenticationServiceInterface
{
    public function attempt(LoginCredentialsData $credentials): LoginResultData;

    public function completeTwoFactorChallenge(string $code): bool;

    public function logout(): void;

    public function user(): ?User;
}
