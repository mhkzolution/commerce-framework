<?php

declare(strict_types=1);

namespace Commerce\Documents\Handlers;

use Commerce\Core\Exceptions\DomainException;
use Commerce\Documents\Contracts\DocumentTypeHandler;
use Commerce\Documents\DTO\BuyerTaxData;
use Commerce\Documents\DTO\CompanyProfileData;
use Commerce\Documents\Enums\DocumentType;
use Commerce\Documents\Models\Document;
use Commerce\Orders\Models\Order;
use DateTimeInterface;

final class TaxInvoiceHandler implements DocumentTypeHandler
{
    public const SCHEMA_VERSION = 1;

    public function type(): DocumentType
    {
        return DocumentType::TaxInvoice;
    }

    public function prefix(): string
    {
        return $this->type()->prefix();
    }

    public function numberWidth(): int
    {
        return max(1, (int) config('documents.number_width', 6));
    }

    public function sourceType(): string
    {
        return Order::REFERENCE_TYPE;
    }

    public function assertCanIssue(Order $order, ?Document $existing): void
    {
        if ($existing instanceof Document) {
            throw new DomainException('A tax invoice already exists for this order.');
        }

        if ($order->isCancelled()) {
            throw new DomainException('A tax invoice cannot be issued for a cancelled order.');
        }
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function buildPayload(array $context): array
    {
        $order = $context['source'] ?? $context['order'] ?? null;
        $seller = $context['seller'] ?? null;
        $buyer = $context['buyer'] ?? null;
        $number = $context['number'] ?? null;
        $issuedAt = $context['issued_at'] ?? null;

        if (! $order instanceof Order) {
            throw new DomainException('Tax invoice payload requires an order source.');
        }

        if (! $seller instanceof CompanyProfileData) {
            throw new DomainException('Tax invoice payload requires seller company data.');
        }

        if (! $buyer instanceof BuyerTaxData) {
            throw new DomainException('Tax invoice payload requires buyer tax data.');
        }

        if (! is_string($number) || $number === '') {
            throw new DomainException('Tax invoice payload requires a document number.');
        }

        if (! $issuedAt instanceof DateTimeInterface) {
            throw new DomainException('Tax invoice payload requires issued_at.');
        }

        $order->loadMissing('lineItems');

        $lines = [];

        foreach ($order->lineItems as $line) {
            $lines[] = [
                'name' => (string) $line->name,
                'sku' => $line->sku,
                'quantity' => (int) $line->quantity,
                'unit_price' => (int) $line->unit_price,
                'line_total' => (int) $line->line_total,
            ];
        }

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'document' => [
                'type' => $this->type()->value,
                'number' => $number,
                'issued_at' => $issuedAt->format(DATE_ATOM),
            ],
            'seller' => $seller->toPayload(),
            'buyer' => $buyer->toPayload(),
            'source' => [
                'type' => $this->sourceType(),
                'id' => (int) $order->getKey(),
                'uuid' => $order->uuid,
                'number' => $order->order_number,
            ],
            'lines' => $lines,
            'totals' => [
                'subtotal' => (int) $order->subtotal,
                'discount_total' => (int) $order->discount_total,
                'tax_total' => (int) $order->tax_total,
                'shipping_total' => (int) $order->shipping_total,
                'grand_total' => (int) $order->grand_total,
                'currency' => (string) $order->currency,
            ],
        ];
    }
}
