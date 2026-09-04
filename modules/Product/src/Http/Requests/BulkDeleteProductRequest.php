<?php

declare(strict_types=1);

namespace Commerce\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class BulkDeleteProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuids' => ['required', 'array', 'min:1', 'max:500'],
            'uuids.*' => ['required', 'uuid'],
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
        ];
    }
}
