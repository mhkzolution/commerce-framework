<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Requests;

use Commerce\Catalog\Models\Attribute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $attribute = Attribute::query()->where('uuid', $this->route('attribute'))->first();

        return [
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('attributes', 'code')->ignore($attribute?->id),
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
