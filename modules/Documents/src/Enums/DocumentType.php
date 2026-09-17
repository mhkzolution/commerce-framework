<?php

declare(strict_types=1);

namespace Commerce\Documents\Enums;

enum DocumentType: string
{
    case TaxInvoice = 'tax_invoice';
    case Receipt = 'receipt';
    case Quotation = 'quotation';
    case DeliveryNote = 'delivery_note';
    case CreditNote = 'credit_note';

    public function prefix(): string
    {
        return match ($this) {
            self::TaxInvoice => 'INV',
            self::Receipt => 'REC',
            self::Quotation => 'QUO',
            self::DeliveryNote => 'DN',
            self::CreditNote => 'CN',
        };
    }

    public function printView(): string
    {
        return match ($this) {
            self::TaxInvoice => 'documents::print.tax-invoice',
            default => 'documents::print.generic',
        };
    }
}
