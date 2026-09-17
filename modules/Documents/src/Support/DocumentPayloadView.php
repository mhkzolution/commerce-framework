<?php

declare(strict_types=1);

namespace Commerce\Documents\Support;

use Commerce\Documents\Models\Document;
use Commerce\Orders\Support\AddressFormatter;
use Illuminate\Support\Carbon;

final readonly class DocumentPayloadView
{
    /**
     * @param  array<string, mixed>  $seller
     * @param  array<string, mixed>  $buyer
     * @param  list<array<string, mixed>>  $lines
     * @param  array<string, mixed>  $totals
     * @param  array<string, mixed>  $source
     */
    public function __construct(
        public Document $document,
        public string $number,
        public string $issuedAt,
        public array $seller,
        public array $buyer,
        public array $lines,
        public array $totals,
        public array $source,
    ) {}

    public static function fromDocument(Document $document): self
    {
        $payload = is_array($document->payload) ? $document->payload : [];
        $meta = is_array($payload['document'] ?? null) ? $payload['document'] : [];
        $seller = is_array($payload['seller'] ?? null) ? $payload['seller'] : [];
        $buyer = is_array($payload['buyer'] ?? null) ? $payload['buyer'] : [];
        $totals = is_array($payload['totals'] ?? null) ? $payload['totals'] : [];
        $source = is_array($payload['source'] ?? null) ? $payload['source'] : [];
        $lines = [];

        foreach ($payload['lines'] ?? [] as $line) {
            if (is_array($line)) {
                $lines[] = $line;
            }
        }

        $issuedAt = (string) ($meta['issued_at'] ?? $document->issued_at?->toIso8601String() ?? '');

        return new self(
            document: $document,
            number: (string) ($meta['number'] ?? $document->number),
            issuedAt: $issuedAt,
            seller: $seller,
            buyer: $buyer,
            lines: $lines,
            totals: $totals,
            source: $source,
        );
    }

    public function issuedAtDisplay(): string
    {
        if ($this->issuedAt === '') {
            return '';
        }

        try {
            return Carbon::parse($this->issuedAt)->timezone(config('app.timezone'))->format('d/m/Y H:i');
        } catch (\Throwable) {
            return $this->issuedAt;
        }
    }

    public function sellerName(): string
    {
        return trim((string) ($this->seller['name'] ?? ''));
    }

    public function sellerTaxId(): string
    {
        return trim((string) ($this->seller['tax_id'] ?? ''));
    }

    public function sellerBranchNo(): string
    {
        return trim((string) ($this->seller['branch_no'] ?? ''));
    }

    public function sellerAddress(): string
    {
        return trim((string) ($this->seller['address'] ?? ''));
    }

    public function sellerPhone(): string
    {
        return trim((string) ($this->seller['phone'] ?? ''));
    }

    public function sellerEmail(): string
    {
        return trim((string) ($this->seller['email'] ?? ''));
    }

    public function buyerName(): string
    {
        return trim((string) ($this->buyer['company_name'] ?? ''));
    }

    public function buyerTaxId(): string
    {
        return trim((string) ($this->buyer['tax_id'] ?? ''));
    }

    public function buyerBranchNo(): string
    {
        return trim((string) ($this->buyer['branch_no'] ?? ''));
    }

    /**
     * @return list<string>
     */
    public function buyerAddressLines(): array
    {
        $address = is_array($this->buyer['billing_address'] ?? null) ? $this->buyer['billing_address'] : [];

        return AddressFormatter::lines($address);
    }

    public function currency(): string
    {
        return (string) ($this->totals['currency'] ?? $this->document->currency);
    }

    public function money(string $key): string
    {
        $amount = (int) ($this->totals[$key] ?? 0);

        return number_format($amount / 100, 2);
    }

    public function lineMoney(mixed $amount): string
    {
        return number_format(((int) $amount) / 100, 2);
    }

    public function relatedOrderNumber(): string
    {
        return trim((string) ($this->source['number'] ?? ''));
    }

    public function fontPath(): string
    {
        return DocumentFont::path();
    }

    public function fontUri(): string
    {
        return DocumentFont::fileUri();
    }

    public function fontBoldUri(): string
    {
        return DocumentFont::boldFileUri();
    }

    public function fontFamily(): string
    {
        return DocumentFont::family();
    }
}
