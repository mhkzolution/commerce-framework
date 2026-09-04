<?php

declare(strict_types=1);

namespace Commerce\WarehouseScanner\Http\Requests;

use Commerce\Barcode\Support\BarcodeSkuNormalizer;
use Illuminate\Foundation\Http\FormRequest;

final class LookupSkuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $sku = $this->input('sku');

        if (is_string($sku)) {
            $this->merge(['sku' => BarcodeSkuNormalizer::normalize($sku)]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:128'],
        ];
    }
}
