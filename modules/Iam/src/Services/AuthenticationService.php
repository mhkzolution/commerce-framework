<?php

declare(strict_types=1);

namespace Commerce\Iam\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Iam\Contracts\Authentication\AuthenticationServiceInterface;
use Commerce\Iam\Contracts\TwoFactor\TwoFactorServiceInterface;
use Commerce\Iam\DTO\LoginCredentialsData;
use Commerce\Iam\DTO\LoginResultData;
use Commerce\Iam\DTO\LoginStatus;
use Commerce\Iam\Models\User;
use Illuminate\Support\Facades\Auth;

final class AuthenticationService extends BaseService implements AuthenticationServiceInterface
{
    private const string PENDING_TWO_FACTOR_KEY = 'iam.pending_two_factor_user_id';

    public function __construct(
        private readonly TwoFactorServiceInterface $twoFactor,
    ) {}

    public function attempt(LoginCredentialsData $credentials): LoginResultData
    {
        $remember = $credentials->remember;

        if (! Auth::validate([
            'email' => $credentials->email,
            'password' => $credentials->password,
            'status' => 'active',
        ])) {
            return new LoginResultData(LoginStatus::Failed);
        }

        /** @var User $user */
        $user = User::query()->where('email', $credentials->email)->firstOrFail();

        if ($this->twoFactor->isEnabled($user) || ($this->twoFactor->isRequired() && config('iam.two_factor.enabled', false))) {
            session()->put(self::PENDING_TWO_FACTOR_KEY, $user->id);
            session()->put('iam.remember_login', $remember);

            return new LoginResultData(LoginStatus::TwoFactorRequired, $user);
        }

        Auth::login($user, $remember);
        $this->markLoggedIn($user);

        return new LoginResultData(LoginStatus::Success, $user);
    }

    public function completeTwoFactorChallenge(string $code): bool
    {
        $userId = session()->pull(self::PENDING_TWO_FACTOR_KEY);

        if ($userId === null) {
            return false;
        }

        $user = User::query()->find($userId);

        if (! $user instanceof User || ! $this->twoFactor->verify($user, $code)) {
            return false;
        }

        $remember = (bool) session()->pull('iam.remember_login', false);
        Auth::login($user, $remember);
        $this->markLoggedIn($user);

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

    private function markLoggedIn(User $user): void
    {
        $user->forceFill(['last_login_at' => now()])->save();
    }
}
