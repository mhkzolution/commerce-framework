<?php

declare(strict_types=1);

namespace Commerce\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateOrderNotesRequest extends FormRequest
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
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'customer_note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
