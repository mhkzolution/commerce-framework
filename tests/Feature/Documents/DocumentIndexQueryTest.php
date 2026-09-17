<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use Commerce\Documents\Enums\DocumentStatus;
use Commerce\Documents\Enums\DocumentType;
use Commerce\Documents\Models\Document;
use Commerce\Documents\Services\DocumentQueryService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class DocumentIndexQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_uses_indexed_number_prefix_and_ignores_payload(): void
    {
        $match = $this->document('INV-202609-000001', 'Mina Shore', 'ORD-9');
        $this->document('INV-202609-000002', 'Other Co', 'ORD-8');
        $this->document('REC-202609-000001', 'Mina Shore', 'ORD-7');

        $query = app(DocumentQueryService::class);

        $this->assertSame(['INV-202609-000001'], $this->numbers($query->paginate(search: 'INV-202609-000001')));
        $this->assertSame(['INV-202609-000002', 'INV-202609-000001'], $this->numbers($query->paginate(search: 'INV-202609')));
        $this->assertSame([], $this->numbers($query->paginate(search: 'Mina Shore')));
        $this->assertSame([], $this->numbers($query->paginate(search: 'ORD-9')));
        $this->assertContains($match->number, $this->numbers($query->paginate(type: 'tax_invoice')));
    }

    public function test_search_sql_does_not_touch_payload_json(): void
    {
        $this->document('INV-202609-000001', 'Mina Shore', 'ORD-9');

        DB::flushQueryLog();
        DB::enableQueryLog();

        app(DocumentQueryService::class)->paginate(search: 'INV-202609');

        $sql = strtolower(collect(DB::getQueryLog())->pluck('query')->implode(' '));

        $this->assertStringContainsString('number', $sql);
        $this->assertStringContainsString('like', $sql);
        $this->assertStringNotContainsString('payload', $sql);
        $this->assertStringNotContainsString('json_extract', $sql);
    }

    /**
     * @param  LengthAwarePaginator<int, Document>  $page
     * @return list<string>
     */
    private function numbers($page): array
    {
        return collect($page->items())->pluck('number')->all();
    }

    private function document(string $number, string $buyer, string $orderNumber): Document
    {
        return Document::query()->create([
            'type' => str_starts_with($number, 'REC') ? DocumentType::Receipt : DocumentType::TaxInvoice,
            'number' => $number,
            'source_type' => 'order',
            'source_id' => 1,
            'status' => DocumentStatus::Issued,
            'issued_at' => now(),
            'payload' => [
                'schema_version' => 1,
                'buyer' => ['company_name' => $buyer],
                'source' => ['number' => $orderNumber],
            ],
            'grand_total' => 0,
            'currency' => 'THB',
        ]);
    }
}
