<?php

declare(strict_types=1);

namespace Commerce\Customers\Http\Requests;

use Commerce\Customers\Http\Requests\Concerns\ValidatesRecaptcha;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorefrontLoginRequest extends FormRequest
{
    use ValidatesRecaptcha;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $mode = $this->input('login_mode', 'email');

        return array_merge([
            'login_mode' => ['nullable', Rule::in(['email', 'phone', 'otp'])],
            'email' => [Rule::requiredIf($mode === 'email'), 'nullable', 'email'],
            'phone' => [Rule::requiredIf($mode === 'phone'), 'nullable', 'string', 'max:32'],
            'identifier' => [Rule::requiredIf($mode === 'otp'), 'nullable', 'string', 'max:255'],
            'otp' => [Rule::requiredIf($mode === 'otp'), 'nullable', 'string', 'max:8'],
            'password' => [Rule::requiredIf(in_array($mode, ['email', 'phone'], true)), 'nullable', 'string'],
            'remember' => ['nullable', 'boolean'],
        ], $this->recaptchaRules());
    }
}
