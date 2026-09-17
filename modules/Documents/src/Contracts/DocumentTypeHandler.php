<?php

declare(strict_types=1);

namespace Commerce\Documents\Contracts;

use Commerce\Documents\Enums\DocumentType;

/**
 * Contract every document type (Receipt, Quotation, Delivery Note, Credit Note, …)
 * must implement. Registering a handler that skips these methods will not boot.
 *
 * Frozen payload keys required of every type:
 * schema_version, document.{type,number,issued_at}, source.{type,id}, lines[], totals.{grand_total,currency}.
 */
interface DocumentTypeHandler
{
    public function type(): DocumentType;

    public function prefix(): string;

    public function numberWidth(): int;

    /**
     * Build the immutable JSON snapshot stored on documents.payload.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function buildPayload(array $context): array;
}
