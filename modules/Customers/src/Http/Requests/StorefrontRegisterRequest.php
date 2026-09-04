<?php

declare(strict_types=1);

namespace Commerce\Customers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StorefrontRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:customers,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['prohibited'],
        ];
    }
}
