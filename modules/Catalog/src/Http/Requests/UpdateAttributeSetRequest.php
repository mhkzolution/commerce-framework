<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Requests;

use Commerce\Catalog\Models\AttributeSet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAttributeSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $set = AttributeSet::query()->where('uuid', $this->route('attribute_set'))->first();

        return [
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('attribute_sets', 'code')->ignore($set?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'attribute_ids' => ['nullable', 'array'],
            'attribute_ids.*' => ['integer', 'exists:attributes,id'],
        ];
    }
}
