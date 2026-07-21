<?php

declare(strict_types=1);

namespace Commerce\Shipping\Http\Requests;

use Commerce\Shipping\Models\ShippingMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateShippingMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var ShippingMethod $method */
        $method = $this->route('method');

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('shipping_methods', 'code')->ignore($method->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'free_above' => ['nullable', 'numeric', 'min:0'],
            'min_subtotal' => ['nullable', 'numeric', 'min:0'],
            'max_subtotal' => ['nullable', 'numeric', 'min:0'],
            'countries' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
