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
            'paper_size' => ['required', 'string', 'max:32'],
            'rows' => ['required', 'integer', 'min:1', 'max:50'],
            'columns' => ['required', 'integer', 'min:1', 'max:20'],
            'margin_top' => ['required', 'numeric', 'min:0'],
            'margin_right' => ['required', 'numeric', 'min:0'],
            'margin_bottom' => ['required', 'numeric', 'min:0'],
            'margin_left' => ['required', 'numeric', 'min:0'],
            'spacing_horizontal' => ['required', 'numeric', 'min:0'],
            'spacing_vertical' => ['required', 'numeric', 'min:0'],
            'label_width' => ['required', 'numeric', 'min:1'],
            'label_height' => ['required', 'numeric', 'min:1'],
            'label_orientation' => ['required', 'string', 'in:horizontal,vertical'],
            ...self::labelStyleRules(),
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
