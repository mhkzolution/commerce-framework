<?php

declare(strict_types=1);

namespace Commerce\Documents\DTO;

use Commerce\Core\Exceptions\DomainException;
use Commerce\Documents\Models\CustomerTaxProfile;
use Commerce\Support\DTO\DataTransferObject;

final readonly class BuyerTaxData extends DataTransferObject
{
    /**
     * @param  array<string, mixed>  $billingAddress
     */
    public function __construct(
        public string $companyName,
        public string $taxId,
        public string $branchNo,
        public array $billingAddress,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $address = is_array($data['billing_address'] ?? null) ? $data['billing_address'] : [];

        return new self(
            companyName: trim((string) ($data['company_name'] ?? '')),
            taxId: self::digits((string) ($data['tax_id'] ?? '')),
            branchNo: self::branchNo((string) ($data['branch_no'] ?? '')),
            billingAddress: self::normalizeAddress($address),
        );
    }

    public function assertComplete(): void
    {
        if ($this->companyName === '' || strlen($this->taxId) !== 13) {
            throw new DomainException('Buyer tax information is incomplete.');
        }

        $line1 = trim((string) ($this->billingAddress['line1'] ?? ''));

        if ($line1 === '') {
            throw new DomainException('Buyer tax information is incomplete.');
        }
    }

    /**
     * @return array{company_name: string, tax_id: string, branch_no: string, billing_address: array<string, mixed>}
     */
    public function toFormArray(): array
    {
        return [
            'company_name' => $this->companyName,
            'tax_id' => $this->taxId,
            'branch_no' => $this->branchNo,
            'billing_address' => $this->billingAddress,
        ];
    }

    /**
     * @return array{company_name: string, tax_id: string, branch_no: string, billing_address: array<string, mixed>}
     */
    public function toPayload(): array
    {
        return $this->toFormArray();
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array<string, string>
     */
    public static function normalizeAddress(array $address): array
    {
        $normalized = [];

        foreach ([
            'recipient_name',
            'line1',
            'line2',
            'city',
            'district',
            'subdistrict',
            'state',
            'province',
            'postal_code',
            'country_code',
            'phone',
        ] as $key) {
            $value = $address[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                $normalized[$key] = trim($value);
            }
        }

        return $normalized;
    }

    public static function digits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    public static function branchNo(string $value): string
    {
        $digits = self::digits($value);

        if ($digits === '') {
            return CustomerTaxProfile::HEAD_OFFICE_BRANCH;
        }

        return str_pad(substr($digits, 0, 5), 5, '0', STR_PAD_LEFT);
    }
}
