<?php

declare(strict_types=1);

namespace Commerce\Documents\Contracts;

use Commerce\Documents\Enums\DocumentRelationType;
use Commerce\Documents\Enums\DocumentType;
use Commerce\Documents\Models\Document;
use Commerce\Documents\Models\DocumentEvent;
use Commerce\Documents\Models\DocumentRelation;
use DateTimeInterface;

interface DocumentServiceInterface
{
    public function allocateNumber(DocumentType $type, ?DateTimeInterface $at = null, ?int $tenantId = null): string;

    public function relate(Document $parent, Document $child, DocumentRelationType $relationType): DocumentRelation;

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function recordEvent(Document $document, string $event, ?int $createdBy = null, ?array $payload = null): DocumentEvent;
}
