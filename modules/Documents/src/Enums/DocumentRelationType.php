<?php

declare(strict_types=1);

namespace Commerce\Documents\Enums;

enum DocumentRelationType: string
{
    case CreditNoteOf = 'credit_note_of';
    case ReceiptOf = 'receipt_of';
    case DeliveryNoteOf = 'delivery_note_of';
    case ConvertedFromQuotation = 'converted_from_quotation';
}
