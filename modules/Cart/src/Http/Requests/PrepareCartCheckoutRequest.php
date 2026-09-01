<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PrepareCartCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'string', 'uuid'],
        ];
    }
}
