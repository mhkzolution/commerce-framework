<?php

declare(strict_types=1);

namespace Commerce\Iam\Contracts\Security;

use Commerce\Iam\Models\User;

interface PasswordResetServiceInterface
{
    public function sendResetLink(string $email): bool;

    public function reset(string $email, string $token, string $password): bool;

    public function createToken(User $user): string;
}
