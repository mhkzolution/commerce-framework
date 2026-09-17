<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use Commerce\Core\Models\Tenant;
use Commerce\Core\Tenant\TenantContext;
use Commerce\Documents\DocumentsServiceProvider;
use Commerce\Documents\Enums\DocumentType;
use Commerce\Documents\Models\DocumentSequence;
use Commerce\Documents\Registry\DocumentTypeRegistry;
use Commerce\Documents\Services\DocumentNumberGenerator;
use Commerce\Documents\Services\DocumentSequenceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class DocumentNumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_numbers_increment_sequentially_for_the_same_period(): void
    {
        Carbon::setTestNow('2026-09-17 10:00:00');

        $service = app(DocumentSequenceService::class);

        $this->assertSame('INV-202609-000001', $service->allocate(DocumentType::TaxInvoice));
        $this->assertSame('INV-202609-000002', $service->allocate(DocumentType::TaxInvoice));
        $this->assertSame('INV-202609-000003', $service->allocate(DocumentType::TaxInvoice));
        $this->assertSame('REC-202609-000001', $service->allocate(DocumentType::Receipt));
    }

    public function test_sequence_resets_when_the_month_rolls_over(): void
    {
        $service = app(DocumentSequenceService::class);

        Carbon::setTestNow('2026-09-30 23:59:00');
        $this->assertSame('INV-202609-000001', $service->allocate(DocumentType::TaxInvoice));
        $this->assertSame('INV-202609-000002', $service->allocate(DocumentType::TaxInvoice));

        Carbon::setTestNow('2026-10-01 00:00:00');
        $this->assertSame('INV-202610-000001', $service->allocate(DocumentType::TaxInvoice));
    }

    public function test_sequences_are_isolated_per_tenant(): void
    {
        config(['commerce.tenant.enabled' => true]);

        $tenantA = Tenant::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
        ]);
        $tenantB = Tenant::query()->create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'status' => 'active',
        ]);

        $service = app(DocumentSequenceService::class);
        $at = Carbon::parse('2026-09-17');

        $this->assertSame('INV-202609-000001', $service->allocate(DocumentType::TaxInvoice, $at, $tenantA->id));
        $this->assertSame('INV-202609-000002', $service->allocate(DocumentType::TaxInvoice, $at, $tenantA->id));
        $this->assertSame('INV-202609-000001', $service->allocate(DocumentType::TaxInvoice, $at, $tenantB->id));

        $this->assertSame(2, DocumentSequence::query()->count());
    }

    public function test_tenant_context_scopes_allocation_without_an_explicit_tenant_id(): void
    {
        config(['commerce.tenant.enabled' => true]);

        $tenant = Tenant::query()->create([
            'name' => 'Context Tenant',
            'slug' => 'context-tenant',
            'status' => 'active',
        ]);

        app(TenantContext::class)->set($tenant);

        $this->assertSame(
            'INV-202609-000001',
            app(DocumentSequenceService::class)->allocate(DocumentType::TaxInvoice, Carbon::parse('2026-09-17')),
        );
        $this->assertSame(1, DocumentSequence::query()->where('tenant_id', $tenant->id)->count());
    }

    public function test_concurrent_allocation_does_not_duplicate_numbers(): void
    {
        $database = storage_path('framework/testing-document-seq-'.uniqid('', true).'.sqlite');
        touch($database);

        try {
            $this->createSequenceTable($database);

            $workers = 4;
            $perWorker = 5;
            $processes = [];

            foreach (range(1, $workers) as $ignored) {
                $process = new Process([
                    PHP_BINARY,
                    base_path('tests/Feature/Documents/allocate_document_number_worker.php'),
                    $database,
                    (string) $perWorker,
                    '202609',
                    base_path(),
                ], base_path(), $this->workerEnv($database));
                $process->setTimeout(60);
                $process->start();
                $processes[] = $process;
            }

            $numbers = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue(
                    $process->isSuccessful(),
                    $process->getErrorOutput()."\n".$process->getOutput(),
                );

                $chunk = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
                $this->assertIsArray($chunk);
                $numbers = array_merge($numbers, $chunk);
            }

            $this->assertCount($workers * $perWorker, $numbers);
            $this->assertCount($workers * $perWorker, array_unique($numbers));
            $this->assertContains('INV-202609-000001', $numbers);
            $this->assertContains('INV-202609-000020', $numbers);
        } finally {
            @unlink($database);
            @unlink($database.'-wal');
            @unlink($database.'-shm');
        }
    }

    public function test_number_generator_matches_prefix_period_and_width(): void
    {
        $generator = app(DocumentNumberGenerator::class);

        $this->assertSame('INV-202609-000001', $generator->format(DocumentType::TaxInvoice, '202609', 1));
        $this->assertSame('CN-202609-000042', $generator->format(DocumentType::CreditNote, '202609', 42));
    }

    public function test_registry_registers_the_tax_invoice_handler(): void
    {
        $registry = app(DocumentTypeRegistry::class);

        $this->assertTrue($registry->has(DocumentType::TaxInvoice));
        $this->assertSame(DocumentType::TaxInvoice, $registry->handlerFor(DocumentType::TaxInvoice)->type());
        $this->assertNotNull($this->app->getProvider(DocumentsServiceProvider::class));
    }

    /**
     * @return array<string, string>
     */
    private function workerEnv(string $database): array
    {
        $env = $_ENV;
        $env['DB_CONNECTION'] = 'sqlite';
        $env['DB_DATABASE'] = $database;
        $env['APP_ENV'] = 'testing';
        $env['APP_KEY'] = (string) config('app.key');

        return $env;
    }

    private function createSequenceTable(string $database): void
    {
        config([
            'database.connections.documents_seq' => [
                'driver' => 'sqlite',
                'database' => $database,
                'prefix' => '',
                'foreign_key_constraints' => false,
                'busy_timeout' => 15000,
                'journal_mode' => 'wal',
                'transaction_mode' => 'IMMEDIATE',
            ],
        ]);

        Schema::connection('documents_seq')->create('document_sequences', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(0);
            $table->string('type', 32);
            $table->char('period', 6);
            $table->unsignedInteger('last_value')->default(0);
            $table->unique(['tenant_id', 'type', 'period']);
        });

        DB::connection('documents_seq')->disconnect();
    }
}
