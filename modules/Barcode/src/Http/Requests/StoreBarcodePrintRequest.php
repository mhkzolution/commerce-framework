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
            'template_id' => ['required', 'integer', 'exists:barcode_templates,id'],
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
        ];
    }
}
