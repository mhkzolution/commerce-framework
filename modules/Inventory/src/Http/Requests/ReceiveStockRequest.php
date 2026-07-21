<?php

declare(strict_types=1);

namespace Commerce\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReceiveStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
