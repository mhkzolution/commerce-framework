<?php

declare(strict_types=1);

namespace Commerce\Documents\Http\Requests\Admin;

use Commerce\Documents\DTO\BuyerTaxData;
use Commerce\Documents\Models\CustomerTaxProfile;
use Illuminate\Foundation\Http\FormRequest;

abstract class BuyerTaxFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $taxId = $this->input('tax_id');
        $branchNo = $this->input('branch_no');
        $address = $this->input('billing_address');

        if (is_array($address)) {
            $city = trim((string) ($address['city'] ?? ''));
            $district = trim((string) ($address['district'] ?? ''));
            $province = trim((string) ($address['province'] ?? ''));
            $state = trim((string) ($address['state'] ?? ''));

            if ($city === '' && $district !== '') {
                $address['city'] = $district;
            }

            if ($state === '' && $province !== '') {
                $address['state'] = $province;
            }
        }

        $payload = [
            'tax_id' => is_string($taxId) ? BuyerTaxData::digits($taxId) : $taxId,
            'branch_no' => is_string($branchNo) && trim($branchNo) !== ''
                ? BuyerTaxData::branchNo($branchNo)
                : CustomerTaxProfile::HEAD_OFFICE_BRANCH,
        ];

        if (is_array($address)) {
            $payload['billing_address'] = $address;
        }

        $this->merge($payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'tax_id' => ['required', 'digits:13'],
            'branch_no' => ['required', 'digits:5'],
            'billing_address' => ['required', 'array'],
            'billing_address.recipient_name' => ['nullable', 'string', 'max:255'],
            'billing_address.line1' => ['required', 'string', 'max:255'],
            'billing_address.line2' => ['nullable', 'string', 'max:255'],
            'billing_address.city' => ['nullable', 'string', 'max:100'],
            'billing_address.district' => ['nullable', 'string', 'max:100'],
            'billing_address.subdistrict' => ['nullable', 'string', 'max:100'],
            'billing_address.state' => ['nullable', 'string', 'max:100'],
            'billing_address.province' => ['nullable', 'string', 'max:100'],
            'billing_address.postal_code' => ['nullable', 'string', 'max:20'],
            'billing_address.country_code' => ['nullable', 'string', 'max:2'],
            'billing_address.phone' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function buyer(): BuyerTaxData
    {
        return BuyerTaxData::fromArray($this->validated());
    }
}
