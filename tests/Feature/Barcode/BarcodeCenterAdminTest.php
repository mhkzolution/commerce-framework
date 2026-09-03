<?php

declare(strict_types=1);

namespace Tests\Feature\Barcode;

use Commerce\Barcode\Models\BarcodePrintJob;
use Commerce\Barcode\Models\BarcodeTemplate;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Marketplace\Models\Seller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class BarcodeCenterAdminTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        $this->artisan('iam:sync-permissions', ['--assign-super-admin' => true]);
    }

    public function test_barcode_workspace_is_accessible(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.barcode.index'))
            ->assertOk()
            ->assertSee(__('barcode::admin.title'));
    }

    public function test_barcode_search_returns_real_products_by_sku(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'BC-SEARCH-001');

        $this->actingAs(User::query()->first())
            ->getJson(route('admin.barcode.search', ['q' => 'BC-SEARCH-001']))
            ->assertOk()
            ->assertJsonPath('data.0.sku', $variant->sku);
    }

    public function test_barcode_exact_search_returns_single_match(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'BC-EXACT-001');

        $this->actingAs(User::query()->first())
            ->getJson(route('admin.barcode.search', ['q' => 'BC-EXACT-001', 'exact' => 1]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sku', $variant->sku);
    }

    public function test_barcode_exact_search_strips_scanner_control_characters(): void
    {
        $variant = $this->createPurchasableProduct(sku: '26300586');

        $this->actingAs(User::query()->first())
            ->getJson(route('admin.barcode.search', ['q' => "26300586\r", 'exact' => 1]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sku', $variant->sku);
    }

    public function test_templates_are_seeded_and_listed(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.barcode.templates.index'))
            ->assertOk()
            ->assertSee('A4 4×10');

        $this->assertDatabaseHas('barcode_templates', ['name' => 'A4 4×10', 'is_default' => true]);
    }

    public function test_print_job_can_be_created_and_viewed(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'BC-PRINT-001');
        $template = BarcodeTemplate::query()->where('is_default', true)->first();
        $user = User::query()->first();

        $response = $this->actingAs($user)
            ->postJson(route('admin.barcode.print.store'), [
                'lines' => [[
                    'variant_uuid' => $variant->uuid,
                    'sku' => $variant->sku,
                    'owner_name' => 'Acme Store',
                    'product_name' => 'Test Product',
                    'quantity' => 2,
                ]],
                'settings' => [
                    'paper_size' => 'a4',
                    'rows' => 10,
                    'columns' => 4,
                    'margin_top' => 10,
                    'margin_right' => 10,
                    'margin_bottom' => 10,
                    'margin_left' => 10,
                    'spacing_horizontal' => 2,
                    'spacing_vertical' => 2,
                    'label_width' => 48.5,
                    'label_height' => 25.4,
                    'label_orientation' => 'vertical',
                    'name' => 'A4 4×10',
                ],
                'template_id' => $template?->id,
            ])
            ->assertOk()
            ->assertJsonStructure(['job_uuid', 'print_url', 'pdf_url']);

        $job = BarcodePrintJob::query()->where('uuid', $response->json('job_uuid'))->first();
        $this->assertNotNull($job);
        $this->assertSame(2, $job->label_count);

        $this->actingAs($user)
            ->get(route('admin.barcode.print.show', $job))
            ->assertOk()
            ->assertSee($variant->sku)
            ->assertSee('bc-print-label--vertical', false);

        $this->actingAs($user)
            ->get(route('admin.barcode.print.pdf', $job))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_print_page_applies_custom_label_style_settings(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'BC-STYLE-001');
        $user = User::query()->first();

        $response = $this->actingAs($user)
            ->postJson(route('admin.barcode.print.store'), [
                'lines' => [[
                    'variant_uuid' => $variant->uuid,
                    'sku' => $variant->sku,
                    'owner_name' => 'Custom Store',
                    'product_name' => 'Styled Product',
                    'quantity' => 1,
                ]],
                'settings' => [
                    'paper_size' => 'a4',
                    'rows' => 10,
                    'columns' => 4,
                    'margin_top' => 10,
                    'margin_right' => 10,
                    'margin_bottom' => 10,
                    'margin_left' => 10,
                    'spacing_horizontal' => 2,
                    'spacing_vertical' => 2,
                    'label_width' => 48.5,
                    'label_height' => 25.4,
                    'label_orientation' => 'vertical',
                    'label_padding_top' => 2,
                    'label_padding_right' => 3,
                    'label_padding_bottom' => 2,
                    'label_padding_left' => 3,
                    'label_content_gap' => 1,
                    'label_owner_font_size' => 8,
                    'label_sku_font_size' => 7,
                ],
            ])
            ->assertOk();

        $job = BarcodePrintJob::query()->where('uuid', $response->json('job_uuid'))->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.barcode.print.show', $job))
            ->assertOk()
            ->assertSee('padding: 2mm 3mm 2mm 3mm', false)
            ->assertSee('gap: 1mm', false)
            ->assertSee('font-size: 8pt', false)
            ->assertSee('font-size: 7pt', false);
    }

    public function test_history_lists_print_jobs(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'BC-HIST-001');
        $template = BarcodeTemplate::query()->first();

        $this->actingAs(User::query()->first())
            ->postJson(route('admin.barcode.print.store'), [
                'lines' => [[
                    'variant_uuid' => $variant->uuid,
                    'sku' => $variant->sku,
                    'owner_name' => 'Acme Store',
                    'product_name' => 'History Product',
                    'quantity' => 1,
                ]],
                'settings' => [
                    'paper_size' => 'a4',
                    'rows' => 10,
                    'columns' => 4,
                    'margin_top' => 10,
                    'margin_right' => 10,
                    'margin_bottom' => 10,
                    'margin_left' => 10,
                    'spacing_horizontal' => 2,
                    'spacing_vertical' => 2,
                    'label_width' => 48.5,
                    'label_height' => 25.4,
                ],
                'template_id' => $template?->id,
            ])
            ->assertOk();

        $this->actingAs(User::query()->first())
            ->get(route('admin.barcode.history.index'))
            ->assertOk()
            ->assertSee(__('barcode::admin.history.title'))
            ->assertSee('Super Admin');
    }

    public function test_reprint_endpoint_returns_queue_payload(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'BC-REPRINT-001');

        $create = $this->actingAs(User::query()->first())
            ->postJson(route('admin.barcode.print.store'), [
                'lines' => [[
                    'variant_uuid' => $variant->uuid,
                    'sku' => $variant->sku,
                    'owner_name' => 'Acme Store',
                    'product_name' => 'Reprint Product',
                    'quantity' => 3,
                ]],
                'settings' => [
                    'paper_size' => 'a4',
                    'rows' => 10,
                    'columns' => 4,
                    'margin_top' => 10,
                    'margin_right' => 10,
                    'margin_bottom' => 10,
                    'margin_left' => 10,
                    'spacing_horizontal' => 2,
                    'spacing_vertical' => 2,
                    'label_width' => 48.5,
                    'label_height' => 25.4,
                ],
            ])
            ->assertOk();

        $job = BarcodePrintJob::query()->where('uuid', $create->json('job_uuid'))->firstOrFail();

        $this->actingAs(User::query()->first())
            ->getJson(route('admin.barcode.history.reprint', $job))
            ->assertOk()
            ->assertJsonPath('lines.0.barcode', $variant->sku)
            ->assertJsonPath('lines.0.quantity', 3);
    }

    public function test_manual_barcode_print_job_can_be_created_without_product(): void
    {
        $template = BarcodeTemplate::query()->where('is_default', true)->first();
        $user = User::query()->first();

        $response = $this->actingAs($user)
            ->postJson(route('admin.barcode.print.store'), [
                'lines' => [[
                    'source' => 'MANUAL',
                    'title' => 'Sample Label',
                    'barcode' => 'MANUAL-BC-001',
                    'display_text' => 'DISPLAY-SKU',
                    'owner_name' => 'Acme Store',
                    'quantity' => 3,
                ]],
                'settings' => [
                    'paper_size' => 'a4',
                    'rows' => 10,
                    'columns' => 4,
                    'margin_top' => 10,
                    'margin_right' => 10,
                    'margin_bottom' => 10,
                    'margin_left' => 10,
                    'spacing_horizontal' => 2,
                    'spacing_vertical' => 2,
                    'label_width' => 48.5,
                    'label_height' => 25.4,
                    'label_orientation' => 'vertical',
                ],
                'template_id' => $template?->id,
            ])
            ->assertOk()
            ->assertJsonStructure(['job_uuid', 'print_url', 'pdf_url']);

        $job = BarcodePrintJob::query()->where('uuid', $response->json('job_uuid'))->first();
        $this->assertNotNull($job);
        $this->assertSame(3, $job->label_count);

        $this->actingAs($user)
            ->get(route('admin.barcode.print.show', $job))
            ->assertOk()
            ->assertSee('DISPLAY-SKU', false);

        $this->actingAs($user)
            ->get(route('admin.barcode.print.pdf', $job))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_manual_barcode_reprint_restores_queue_payload(): void
    {
        $user = User::query()->first();

        $create = $this->actingAs($user)
            ->postJson(route('admin.barcode.print.store'), [
                'lines' => [[
                    'source' => 'MANUAL',
                    'title' => 'Manual Reprint Label',
                    'barcode' => 'MANUAL-REPRINT-001',
                    'display_text' => 'MANUAL-REPRINT-001',
                    'owner_name' => 'Acme Store',
                    'quantity' => 2,
                ]],
                'settings' => [
                    'paper_size' => 'a4',
                    'rows' => 10,
                    'columns' => 4,
                    'margin_top' => 10,
                    'margin_right' => 10,
                    'margin_bottom' => 10,
                    'margin_left' => 10,
                    'spacing_horizontal' => 2,
                    'spacing_vertical' => 2,
                    'label_width' => 48.5,
                    'label_height' => 25.4,
                ],
            ])
            ->assertOk();

        $job = BarcodePrintJob::query()->where('uuid', $create->json('job_uuid'))->firstOrFail();

        $this->actingAs($user)
            ->getJson(route('admin.barcode.history.reprint', $job))
            ->assertOk()
            ->assertJsonPath('lines.0.barcode', 'MANUAL-REPRINT-001')
            ->assertJsonPath('lines.0.source', 'MANUAL')
            ->assertJsonPath('lines.0.quantity', 2);
    }

    public function test_legacy_print_payload_is_normalized_on_store(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'BC-LEGACY-001');
        $user = User::query()->first();

        $response = $this->actingAs($user)
            ->postJson(route('admin.barcode.print.store'), [
                'lines' => [[
                    'variant_uuid' => $variant->uuid,
                    'sku' => $variant->sku,
                    'owner_name' => 'Acme Store',
                    'product_name' => 'Legacy Product',
                    'quantity' => 1,
                ]],
                'settings' => [
                    'paper_size' => 'a4',
                    'rows' => 10,
                    'columns' => 4,
                    'margin_top' => 10,
                    'margin_right' => 10,
                    'margin_bottom' => 10,
                    'margin_left' => 10,
                    'spacing_horizontal' => 2,
                    'spacing_vertical' => 2,
                    'label_width' => 48.5,
                    'label_height' => 25.4,
                ],
            ])
            ->assertOk();

        $job = BarcodePrintJob::query()->where('uuid', $response->json('job_uuid'))->firstOrFail();

        $this->assertSame('PRODUCT', $job->payload['lines'][0]['source']);
        $this->assertSame($variant->sku, $job->payload['lines'][0]['barcode']);
    }

    public function test_barcode_generate_endpoint_returns_value(): void
    {
        $this->actingAs(User::query()->first())
            ->getJson(route('admin.barcode.generate', ['strategy' => 'random']))
            ->assertOk()
            ->assertJsonStructure(['barcode', 'strategy']);
    }

    public function test_barcode_generate_numeric_sequence_returns_barcodes(): void
    {
        $this->actingAs(User::query()->first())
            ->getJson(route('admin.barcode.generate', [
                'strategy' => 'numeric_sequence',
                'start' => '15202104',
                'count' => 5,
            ]))
            ->assertOk()
            ->assertJson([
                'strategy' => 'numeric_sequence',
                'barcodes' => ['15202104', '15202105', '15202106', '15202107', '15202108'],
            ]);
    }

    public function test_barcode_generate_numeric_sequence_rejects_invalid_start(): void
    {
        $this->actingAs(User::query()->first())
            ->getJson(route('admin.barcode.generate', [
                'strategy' => 'numeric_sequence',
                'start' => 'ABC',
                'count' => 3,
            ]))
            ->assertUnprocessable()
            ->assertJsonStructure(['message']);
    }

    public function test_manual_barcode_print_job_uses_seller_owner_name(): void
    {
        if (! class_exists(Seller::class)) {
            $this->markTestSkipped('Marketplace module is not available.');
        }

        $seller = Seller::query()->create([
            'name' => 'POR STORE',
            'slug' => 'por-store',
            'status' => 'active',
        ]);
        $user = User::query()->first();

        $response = $this->actingAs($user)
            ->postJson(route('admin.barcode.print.store'), [
                'lines' => [[
                    'source' => 'MANUAL',
                    'title' => 'Manual Label',
                    'barcode' => '1000019',
                    'display_text' => '1000019',
                    'owner_name' => 'POR STORE',
                    'quantity' => 1,
                    'meta' => ['seller_uuid' => $seller->uuid],
                ]],
                'settings' => [
                    'paper_size' => 'a4',
                    'rows' => 10,
                    'columns' => 4,
                    'margin_top' => 10,
                    'margin_right' => 10,
                    'margin_bottom' => 10,
                    'margin_left' => 10,
                    'spacing_horizontal' => 2,
                    'spacing_vertical' => 2,
                    'label_width' => 48.5,
                    'label_height' => 25.4,
                ],
            ])
            ->assertOk();

        $job = BarcodePrintJob::query()->where('uuid', $response->json('job_uuid'))->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.barcode.print.show', $job))
            ->assertOk()
            ->assertSee('POR STORE', false)
            ->assertSee('1000019', false);
    }

    public function test_barcode_workspace_shows_manual_barcode_entry(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.barcode.index'))
            ->assertOk()
            ->assertSee(__('barcode::admin.search.mode_manual'))
            ->assertSee(__('barcode::admin.manual.title'));
    }
}
