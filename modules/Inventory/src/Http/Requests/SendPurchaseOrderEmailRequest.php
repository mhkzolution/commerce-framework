<?php

declare(strict_types=1);

namespace Commerce\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SendPurchaseOrderEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
