<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AddCartLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purchasable_uuid' => ['required', 'uuid'],
            'quantity' => ['required', 'integer', 'min:1'],
            'redirect_to' => ['nullable', 'in:checkout'],
        ];
    }
}
