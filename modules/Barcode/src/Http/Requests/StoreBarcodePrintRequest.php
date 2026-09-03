<?php

declare(strict_types=1);

namespace Commerce\Barcode\Http\Requests;

use Commerce\Barcode\Enums\BarcodeQueueSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreBarcodePrintRequest extends FormRequest
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
        return [
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.source' => ['sometimes', Rule::enum(BarcodeQueueSource::class)],
            'lines.*.title' => ['required_without:lines.*.product_name', 'string', 'max:255'],
            'lines.*.barcode' => ['required_without:lines.*.sku', 'string', 'max:100'],
            'lines.*.display_text' => ['nullable', 'string', 'max:100'],
            'lines.*.owner_name' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'lines.*.thumbnail_url' => ['nullable', 'string', 'max:2048'],
            'lines.*.variant_id' => ['nullable', 'uuid'],
            'lines.*.product_id' => ['nullable', 'uuid'],
            'lines.*.meta' => ['nullable', 'array'],
            'lines.*.variant_uuid' => ['nullable', 'uuid'],
            'lines.*.sku' => ['nullable', 'string', 'max:100'],
            'lines.*.product_name' => ['nullable', 'string', 'max:255'],
            'lines.*.variant_name' => ['nullable', 'string', 'max:255'],
            'settings' => ['required', 'array'],
            'settings.paper_size' => ['required', 'string', 'max:32'],
            'settings.rows' => ['required', 'integer', 'min:1', 'max:50'],
            'settings.columns' => ['required', 'integer', 'min:1', 'max:20'],
            'settings.margin_top' => ['required', 'numeric', 'min:0'],
            'settings.margin_right' => ['required', 'numeric', 'min:0'],
            'settings.margin_bottom' => ['required', 'numeric', 'min:0'],
            'settings.margin_left' => ['required', 'numeric', 'min:0'],
            'settings.spacing_horizontal' => ['required', 'numeric', 'min:0'],
            'settings.spacing_vertical' => ['required', 'numeric', 'min:0'],
            'settings.label_width' => ['required', 'numeric', 'min:1'],
            'settings.label_height' => ['required', 'numeric', 'min:1'],
            'settings.label_orientation' => ['sometimes', 'string', 'in:horizontal,vertical'],
            'settings.label_padding_top' => ['sometimes', 'numeric', 'min:0'],
            'settings.label_padding_right' => ['sometimes', 'numeric', 'min:0'],
            'settings.label_padding_bottom' => ['sometimes', 'numeric', 'min:0'],
            'settings.label_padding_left' => ['sometimes', 'numeric', 'min:0'],
            'settings.label_content_gap' => ['sometimes', 'numeric', 'min:0'],
            'settings.label_owner_font_size' => ['sometimes', 'numeric', 'min:1', 'max:24'],
            'settings.label_sku_font_size' => ['sometimes', 'numeric', 'min:1', 'max:24'],
            'template_id' => ['nullable', 'integer', 'exists:barcode_templates,id'],
        ];
    }
}
