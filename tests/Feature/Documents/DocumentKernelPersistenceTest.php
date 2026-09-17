<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use Commerce\Documents\Enums\DocumentRelationType;
use Commerce\Documents\Enums\DocumentStatus;
use Commerce\Documents\Enums\DocumentType;
use Commerce\Documents\Models\Document;
use Commerce\Documents\Models\DocumentEvent;
use Commerce\Documents\Services\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DocumentKernelPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_documents_store_money_as_integer_minor_units_and_uuid_route_keys(): void
    {
        $document = Document::query()->create([
            'type' => DocumentType::TaxInvoice,
            'number' => 'INV-202609-000001',
            'source_type' => 'order',
            'source_id' => 42,
            'status' => DocumentStatus::Issued,
            'issued_at' => now(),
            'payload' => ['schema_version' => 1],
            'grand_total' => 10700,
            'currency' => 'THB',
        ]);

        $this->assertNotEmpty($document->uuid);
        $this->assertSame('uuid', $document->getRouteKeyName());
        $this->assertSame(10700, $document->fresh()->grand_total);
        $this->assertIsInt($document->fresh()->grand_total);
    }

    public function test_document_service_records_relations_and_events(): void
    {
        $parent = Document::query()->create([
            'type' => DocumentType::TaxInvoice,
            'number' => 'INV-202609-000010',
            'source_type' => 'order',
            'source_id' => 10,
            'status' => DocumentStatus::Issued,
            'issued_at' => now(),
            'payload' => ['schema_version' => 1],
            'grand_total' => 0,
            'currency' => 'THB',
        ]);

        $child = Document::query()->create([
            'type' => DocumentType::CreditNote,
            'number' => 'CN-202609-000001',
            'source_type' => 'order',
            'source_id' => 10,
            'status' => DocumentStatus::Issued,
            'issued_at' => now(),
            'payload' => ['schema_version' => 1],
            'grand_total' => 0,
            'currency' => 'THB',
        ]);

        $service = app(DocumentService::class);
        $relation = $service->relate($parent, $child, DocumentRelationType::CreditNoteOf);
        $event = $service->recordEvent($parent, DocumentEvent::ISSUED, payload: ['via' => 'kernel']);

        $this->assertSame($parent->id, $relation->parent_document_id);
        $this->assertSame($child->id, $relation->child_document_id);
        $this->assertSame(DocumentRelationType::CreditNoteOf, $relation->relation_type);
        $this->assertSame(DocumentEvent::ISSUED, $event->event);
        $this->assertSame(['via' => 'kernel'], $event->payload);
    }
}
