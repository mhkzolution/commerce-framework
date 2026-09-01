<?php

declare(strict_types=1);

namespace Commerce\Customers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StorefrontResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
