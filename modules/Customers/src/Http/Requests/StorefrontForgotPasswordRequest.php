<?php

declare(strict_types=1);

namespace Commerce\Customers\Http\Requests;

use Commerce\Customers\Http\Requests\Concerns\ValidatesRecaptcha;
use Illuminate\Foundation\Http\FormRequest;

final class StorefrontForgotPasswordRequest extends FormRequest
{
    use ValidatesRecaptcha;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'email' => ['required', 'email'],
        ], $this->recaptchaRules());
    }
}
