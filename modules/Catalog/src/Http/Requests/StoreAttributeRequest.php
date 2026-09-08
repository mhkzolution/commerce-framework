<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Requests;

use Commerce\Product\Support\SearchReservedParams;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::notIn(SearchReservedParams::KEYS),
                'unique:attributes,code',
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(array_keys(config('catalog.attribute_types', [])))],
            'is_filterable' => ['nullable', 'boolean'],
            'is_required' => ['nullable', 'boolean'],
            'is_visible' => ['nullable', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
            'options' => ['nullable', 'string'],
        ];
    }
}
