<?php

declare(strict_types=1);

namespace Commerce\Product\Http\Requests;

use Commerce\Catalog\Models\Attribute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateVariantOptionPresetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $uuid = (string) $this->route('variant_option');
        $attributeId = Attribute::query()->where('uuid', $uuid)->value('id');

        return [
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('attributes', 'code')->ignore($attributeId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
            'options' => ['required', 'array', 'min:1'],
            'options.*' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'options.required' => 'กรุณาเพิ่มค่าตัวเลือกอย่างน้อย 1 รายการ',
            'options.min' => 'กรุณาเพิ่มค่าตัวเลือกอย่างน้อย 1 รายการ',
        ];
    }
}
