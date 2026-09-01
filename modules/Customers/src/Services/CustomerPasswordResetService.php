<?php

declare(strict_types=1);

namespace Commerce\Customers\Services;

use Commerce\Customers\Models\Customer;
use Commerce\Customers\Notifications\CustomerResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

final class CustomerPasswordResetService
{
    public function sendResetLink(string $email): bool
    {
        $customer = Customer::query()->where('email', $email)->first();

        if ($customer === null) {
            return true;
        }

        $token = Password::broker('customers')->createToken($customer);
        $customer->notify(new CustomerResetPasswordNotification($token));

        return true;
    }

    public function reset(string $email, string $token, string $password): bool
    {
        $status = Password::broker('customers')->reset(
            [
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $password,
                'token' => $token,
            ],
            function (Customer $customer, string $password): void {
                $customer->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            },
        );

        return $status === Password::PASSWORD_RESET;
    }
}
