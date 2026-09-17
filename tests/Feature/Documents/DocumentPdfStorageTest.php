<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use Commerce\Documents\Enums\DocumentStatus;
use Commerce\Documents\Enums\DocumentType;
use Commerce\Documents\Models\Document;
use Commerce\Documents\Services\DocumentPdfService;
use Commerce\Documents\Support\DocumentFont;
use Commerce\Documents\Support\DocumentPayloadView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class DocumentPdfStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_forget_deletes_cached_pdf_and_clears_path_without_mutating_payload(): void
    {
        $document = Document::query()->create([
            'type' => DocumentType::TaxInvoice,
            'number' => 'INV-202609-000099',
            'source_type' => 'order',
            'source_id' => 1,
            'status' => DocumentStatus::Issued,
            'issued_at' => now(),
            'payload' => [
                'schema_version' => 1,
                'document' => [
                    'type' => DocumentType::TaxInvoice->value,
                    'number' => 'INV-202609-000099',
                    'issued_at' => now()->toAtomString(),
                ],
                'seller' => ['name' => 'Acme Co'],
                'buyer' => ['company_name' => 'Mina Shore'],
                'source' => ['type' => 'order', 'id' => 1, 'number' => 'ORD-1'],
                'lines' => [],
                'totals' => ['grand_total' => 0, 'currency' => 'THB'],
            ],
            'grand_total' => 0,
            'currency' => 'THB',
        ]);

        $pdf = app(DocumentPdfService::class);
        $generated = $pdf->ensureGenerated($document);
        $path = $generated->pdf_path;
        $payload = $generated->payload;

        $this->assertNotNull($path);
        $this->assertTrue(Storage::disk('local')->exists($path));

        $cleared = $pdf->forget($generated);

        $this->assertNull($cleared->pdf_path);
        $this->assertFalse(Storage::disk('local')->exists($path));
        $this->assertSame($payload, $cleared->payload);
        $this->assertSame(DocumentStatus::Issued, $cleared->status);
    }

    public function test_pdf_bundles_sarabun_regular_and_bold(): void
    {
        $this->assertFileExists(DocumentFont::path());
        $this->assertFileExists(DocumentFont::boldPath());
        $this->assertNotSame(
            DocumentFont::path(),
            DocumentFont::boldPath(),
        );

        $document = Document::query()->create([
            'type' => DocumentType::TaxInvoice,
            'number' => 'INV-202609-000100',
            'source_type' => 'order',
            'source_id' => 1,
            'status' => DocumentStatus::Issued,
            'issued_at' => now(),
            'payload' => [
                'schema_version' => 1,
                'document' => [
                    'type' => DocumentType::TaxInvoice->value,
                    'number' => 'INV-202609-000100',
                    'issued_at' => now()->toAtomString(),
                ],
                'seller' => ['name' => 'Acme Co'],
                'buyer' => ['company_name' => 'Mina Shore'],
                'source' => ['type' => 'order', 'id' => 1, 'number' => 'ORD-1'],
                'lines' => [],
                'totals' => ['grand_total' => 0, 'currency' => 'THB'],
            ],
            'grand_total' => 0,
            'currency' => 'THB',
        ]);

        $html = view('documents::print.tax-invoice', [
            'view' => DocumentPayloadView::fromDocument($document),
            'mode' => 'pdf',
        ])->render();

        $this->assertStringContainsString('font-weight: 400', $html);
        $this->assertStringContainsString('font-weight: 700', $html);
        $this->assertStringContainsString('Sarabun-Regular.ttf', $html);
        $this->assertStringContainsString('Sarabun-Bold.ttf', $html);
        $this->assertStringContainsString('ใบกำกับภาษี', $html);

        $pdf = app(DocumentPdfService::class)->render($document);
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertStringContainsString('Sarabun', $pdf);
    }
}
