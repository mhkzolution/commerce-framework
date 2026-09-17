<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use Commerce\Documents\Contracts\DocumentServiceInterface;
use Commerce\Documents\DocumentsServiceProvider;
use Commerce\Documents\Enums\DocumentType;
use Commerce\Documents\Registry\DocumentTypeRegistry;
use Commerce\Documents\Services\DocumentPdfService;
use Commerce\Documents\Services\DocumentQueryService;
use Commerce\Documents\Services\DocumentSequenceService;
use Commerce\Documents\Services\TaxInvoiceIssueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class DocumentsHostWiringTest extends TestCase
{
    use RefreshDatabase;

    public function test_host_enables_and_boots_the_documents_module(): void
    {
        $this->assertTrue(config('commerce.modules.documents'));
        $this->assertNotNull($this->app->getProvider(DocumentsServiceProvider::class));
        $this->assertTrue($this->app->bound(DocumentSequenceService::class));
        $this->assertTrue($this->app->bound(DocumentTypeRegistry::class));
        $this->assertTrue($this->app->bound(DocumentServiceInterface::class));
        $this->assertTrue($this->app->bound(TaxInvoiceIssueService::class));
        $this->assertTrue($this->app->bound(DocumentQueryService::class));
        $this->assertTrue($this->app->bound(DocumentPdfService::class));
        $this->assertTrue(app(DocumentTypeRegistry::class)->has(DocumentType::TaxInvoice));
        $this->assertTrue(Route::has('admin.documents.index'));
        $this->assertTrue(Route::has('admin.documents.show'));
        $this->assertTrue(Route::has('admin.documents.print'));
        $this->assertTrue(Route::has('admin.documents.download'));
    }

    public function test_kernel_tables_are_migrated(): void
    {
        $this->assertTrue(Schema::hasTable('document_sequences'));
        $this->assertTrue(Schema::hasTable('documents'));
        $this->assertTrue(Schema::hasTable('document_relations'));
        $this->assertTrue(Schema::hasTable('document_events'));
        $this->assertTrue(Schema::hasTable('customer_tax_profiles'));
    }
}
