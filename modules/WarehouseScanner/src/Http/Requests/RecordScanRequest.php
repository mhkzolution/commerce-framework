<?php

declare(strict_types=1);

namespace Commerce\WarehouseScanner\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RecordScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'mode' => ['required', 'string', Rule::in(array_keys(config('warehouse-scanner.modes', [])))],
            'sku' => ['required', 'string', 'max:128'],
            'action' => ['required', 'string', 'max:64'],
            'variant_uuid' => ['nullable', 'uuid'],
            'quantity' => ['nullable', 'integer'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
