<?php

declare(strict_types=1);

namespace Commerce\Documents\DTO;

use Commerce\Documents\Models\CustomerTaxProfile;
use Commerce\Support\DTO\DataTransferObject;

final readonly class CompanyProfileData extends DataTransferObject
{
    public function __construct(
        public string $name,
        public string $taxId,
        public string $branchNo,
        public string $address,
        public string $phone,
        public string $email,
        public string $logo,
    ) {}

    /**
     * @param  array<string, mixed>  $values
     */
    public static function fromSettings(array $values): self
    {
        return new self(
            name: trim((string) ($values['name'] ?? '')),
            taxId: BuyerTaxData::digits((string) ($values['tax_id'] ?? '')),
            branchNo: BuyerTaxData::branchNo((string) ($values['branch_no'] ?? CustomerTaxProfile::HEAD_OFFICE_BRANCH)),
            address: trim((string) ($values['address'] ?? '')),
            phone: trim((string) ($values['phone'] ?? '')),
            email: trim((string) ($values['email'] ?? '')),
            logo: trim((string) ($values['logo'] ?? '')),
        );
    }

    public function isIssuable(): bool
    {
        return $this->name !== '' && strlen($this->taxId) === 13;
    }

    /**
     * @return array<string, string>
     */
    public function toPayload(): array
    {
        return [
            'name' => $this->name,
            'tax_id' => $this->taxId,
            'branch_no' => $this->branchNo,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'logo' => $this->logo,
        ];
    }
}
