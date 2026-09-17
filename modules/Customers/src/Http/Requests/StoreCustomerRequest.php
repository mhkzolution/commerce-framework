<?php

declare(strict_types=1);

namespace Commerce\Customers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $address = $this->input('address');
        if (! is_array($address)) {
            return;
        }

        $city = trim((string) ($address['city'] ?? ''));
        $district = trim((string) ($address['district'] ?? ''));
        if ($city === '' && $district !== '') {
            $address['city'] = $district;
            $this->merge(['address' => $address]);
        }
    }

    public function rules(): array
    {
        $isAdminStore = $this->routeIs('admin.customers.store');

        return [
            'email' => ['required', 'email', 'max:255', 'unique:customers,email'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => [$isAdminStore ? 'required' : 'nullable', 'string', 'max:50'],
            'status' => ['required', 'string', Rule::in(array_keys(config('customers.statuses', [])))],
            'password' => [$isAdminStore ? 'required' : 'nullable', 'string', Password::min(8), 'confirmed'],
            'address' => ['nullable', 'array'],
            'address.line1' => ['nullable', 'string', 'max:255'],
            'address.line2' => ['nullable', 'string', 'max:255'],
            'address.city' => ['required_with:address.line1', 'nullable', 'string', 'max:100'],
            'address.district' => ['nullable', 'string', 'max:100'],
            'address.subdistrict' => ['nullable', 'string', 'max:100'],
            'address.state' => ['nullable', 'string', 'max:100'],
            'address.postal_code' => ['required_with:address.line1', 'nullable', 'string', 'max:20'],
            'address.country_code' => ['required_with:address.line1', 'nullable', 'string', 'size:2'],
        ];
    }
}
