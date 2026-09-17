<?php

declare(strict_types=1);

namespace Commerce\Documents\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Documents\Contracts\DocumentServiceInterface;
use Commerce\Documents\Enums\DocumentRelationType;
use Commerce\Documents\Enums\DocumentType;
use Commerce\Documents\Models\Document;
use Commerce\Documents\Models\DocumentEvent;
use Commerce\Documents\Models\DocumentRelation;
use Commerce\Documents\Registry\DocumentTypeRegistry;
use DateTimeInterface;
use Illuminate\Database\QueryException;

final class DocumentService extends BaseService implements DocumentServiceInterface
{
    public function __construct(
        private readonly DocumentSequenceService $sequences,
        private readonly DocumentTypeRegistry $registry,
    ) {}

    public function allocateNumber(DocumentType $type, ?DateTimeInterface $at = null, ?int $tenantId = null): string
    {
        return $this->sequences->allocate($type, $at, $tenantId);
    }

    public function relate(Document $parent, Document $child, DocumentRelationType $relationType): DocumentRelation
    {
        if ($parent->is($child) || (int) $parent->getKey() === (int) $child->getKey()) {
            throw new DomainException('A document cannot be related to itself.');
        }

        try {
            return DocumentRelation::query()->create([
                'parent_document_id' => $parent->getKey(),
                'child_document_id' => $child->getKey(),
                'relation_type' => $relationType,
            ]);
        } catch (QueryException $e) {
            throw new DomainException('Document relation already exists.', previous: $e);
        }
    }

    public function recordEvent(Document $document, string $event, ?int $createdBy = null, ?array $payload = null): DocumentEvent
    {
        return DocumentEvent::query()->create([
            'document_id' => $document->getKey(),
            'event' => $event,
            'payload' => $payload,
            'created_by' => $createdBy,
        ]);
    }

    public function registry(): DocumentTypeRegistry
    {
        return $this->registry;
    }
}
