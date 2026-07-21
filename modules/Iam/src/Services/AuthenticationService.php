<?php

declare(strict_types=1);

namespace Commerce\Iam\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Iam\Contracts\Authentication\AuthenticationServiceInterface;
use Commerce\Iam\DTO\LoginCredentialsData;
use Commerce\Iam\Models\User;
use Illuminate\Support\Facades\Auth;

final class AuthenticationService extends BaseService implements AuthenticationServiceInterface
{
    public function attempt(LoginCredentialsData $credentials): bool
    {
        $remember = $credentials->remember;

        if (! Auth::attempt([
            'email' => $credentials->email,
            'password' => $credentials->password,
            'status' => 'active',
        ], $remember)) {
            return false;
        }

        /** @var User $user */
        $user = Auth::user();
        $user->forceFill(['last_login_at' => now()])->save();

        return true;
    }

    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
    }

    public function user(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
