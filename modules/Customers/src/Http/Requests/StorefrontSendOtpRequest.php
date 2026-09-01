<?php

declare(strict_types=1);

namespace Commerce\Customers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StorefrontSendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255'],
        ];
    }
}
