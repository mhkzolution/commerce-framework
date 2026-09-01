<?php

declare(strict_types=1);

namespace Commerce\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAppearanceRequest extends FormRequest
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
        $hex = ['nullable', 'string', 'regex:/^#([0-9A-Fa-f]{6}|[0-9A-Fa-f]{3})$/'];

        return [
            'primary' => $hex,
            'primary_hover' => $hex,
            'primary_active' => $hex,
            'background' => $hex,
            'surface' => $hex,
            'accent' => $hex,
            'accent_hover' => $hex,
        ];
    }

    public function messages(): array
    {
        return [
            '*.regex' => 'กรุณาใส่รหัสสีแบบ HEX เช่น #2563eb',
        ];
    }
}
