<?php

declare(strict_types=1);

namespace Commerce\Barcode\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreBarcodeTemplateRequest extends FormRequest
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
        return $this->templateRules();
    }

    /**
     * @return array<string, mixed>
     */
    protected function templateRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'preset_code' => ['required', 'string', 'in:a4_40,a4_24,a4_65,thermal_50x30'],
            'label_orientation' => ['required', 'string', 'in:horizontal,vertical'],
            ...self::labelStyleRules(),
            'show_name' => ['sometimes', 'boolean'],
            'show_sku' => ['sometimes', 'boolean'],
            'show_owner' => ['sometimes', 'boolean'],
            'show_barcode' => ['sometimes', 'boolean'],
            'is_favorite' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function labelStyleRules(): array
    {
        return [
            'label_padding_top' => ['required', 'numeric', 'min:0'],
            'label_padding_right' => ['required', 'numeric', 'min:0'],
            'label_padding_bottom' => ['required', 'numeric', 'min:0'],
            'label_padding_left' => ['required', 'numeric', 'min:0'],
            'label_content_gap' => ['required', 'numeric', 'min:0'],
            'label_owner_font_size' => ['required', 'numeric', 'min:1', 'max:24'],
            'label_sku_font_size' => ['required', 'numeric', 'min:1', 'max:24'],
        ];
    }
}
