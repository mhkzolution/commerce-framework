<?php

declare(strict_types=1);

namespace Commerce\Tax\Http\Requests;

use Commerce\Tax\Models\TaxRate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpsertTaxRateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $rate = $this->route('rate');

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('tax_rates', 'code')->ignore($rate instanceof TaxRate ? $rate->id : null)],
            'name' => ['required', 'string', 'max:255'],
            'rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
