<?php

declare(strict_types=1);

namespace Commerce\Iam\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Iam\Contracts\Security\PasswordResetServiceInterface;
use Commerce\Iam\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

final class PasswordResetService extends BaseService implements PasswordResetServiceInterface
{
    public function sendResetLink(string $email): bool
    {
        return Password::broker()->sendResetLink(['email' => $email]) === Password::RESET_LINK_SENT;
    }

    public function reset(string $email, string $token, string $password): bool
    {
        $status = Password::broker()->reset(
            [
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $password,
                'token' => $token,
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        return $status === Password::PASSWORD_RESET;
    }

    public function createToken(User $user): string
    {
        return Password::broker()->createToken($user);
    }
}
