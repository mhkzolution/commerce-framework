<?php

declare(strict_types=1);

namespace Commerce\Documents\Http\Requests\Admin;

use Commerce\Documents\DTO\BuyerTaxData;
use Commerce\Documents\Models\CustomerTaxProfile;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateCompanySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $taxId = $this->input('tax_id');
        $branchNo = $this->input('branch_no');

        $this->merge([
            'tax_id' => is_string($taxId) ? BuyerTaxData::digits($taxId) : $taxId,
            'branch_no' => is_string($branchNo) && trim($branchNo) !== ''
                ? BuyerTaxData::branchNo($branchNo)
                : CustomerTaxProfile::HEAD_OFFICE_BRANCH,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => ['required', 'digits:13'],
            'branch_no' => ['required', 'digits:5'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'logo' => ['nullable', 'uuid'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function companyValues(): array
    {
        $validated = $this->validated();
        $logo = $validated['logo'] ?? null;

        return [
            'name' => trim((string) $validated['name']),
            'tax_id' => (string) $validated['tax_id'],
            'branch_no' => (string) $validated['branch_no'],
            'address' => $this->nullableString($validated['address'] ?? null) ?? '',
            'phone' => $this->nullableString($validated['phone'] ?? null) ?? '',
            'email' => $this->nullableString($validated['email'] ?? null) ?? '',
            'logo' => is_string($logo) && $logo !== '' ? $logo : null,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
