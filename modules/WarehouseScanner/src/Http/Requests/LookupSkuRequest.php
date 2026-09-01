<?php

declare(strict_types=1);

namespace Commerce\WarehouseScanner\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LookupSkuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:128'],
        ];
    }
}
