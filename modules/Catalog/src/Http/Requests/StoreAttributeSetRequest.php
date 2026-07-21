<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreAttributeSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100', 'unique:attribute_sets,code'],
            'name' => ['required', 'string', 'max:255'],
            'attribute_ids' => ['nullable', 'array'],
            'attribute_ids.*' => ['integer', 'exists:attributes,id'],
        ];
    }
}
