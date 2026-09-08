<?php

declare(strict_types=1);

namespace Commerce\Product\Http\Requests;

use Commerce\Product\Support\SearchReservedParams;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class StoreVariantOptionPresetRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $code = $this->input('code');

        if (is_string($code)) {
            $this->merge(['code' => Str::slug($code, '_')]);
        }
    }

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
