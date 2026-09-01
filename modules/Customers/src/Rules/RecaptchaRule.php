<?php

declare(strict_types=1);

namespace Commerce\Customers\Rules;

use Closure;
use Commerce\Customers\Services\RecaptchaVerifier;
use Illuminate\Contracts\Validation\ValidationRule;

final class RecaptchaRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $verifier = app(RecaptchaVerifier::class);

        if (! $verifier->enabled()) {
            return;
        }

        $token = is_string($value) ? $value : null;

        if (! $verifier->verify($token, request()->ip())) {
            $fail(__('customers::auth.recaptcha_failed'));
        }
    }
}
