<?php

declare(strict_types=1);

namespace Commerce\Iam\Contracts\Authentication;

use Commerce\Iam\DTO\LoginCredentialsData;
use Commerce\Iam\Models\User;

interface AuthenticationServiceInterface
{
    public function attempt(LoginCredentialsData $credentials): bool;

    public function logout(): void;

    public function user(): ?User;
}
