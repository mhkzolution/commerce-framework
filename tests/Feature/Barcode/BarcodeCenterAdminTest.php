<?php

declare(strict_types=1);

namespace Tests\Feature\Barcode;

use Commerce\Barcode\Models\BarcodePrintJob;
use Commerce\Barcode\Models\BarcodeTemplate;
use Commerce\Barcode\Services\BarcodeTemplateService;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Marketplace\Models\Seller;
use Tests\Concerns\CreatesPurchasableProduct;

final class BarcodeCenterAdminTest extends BarcodeFeatureTestCase
{
    use CreatesPurchasableProduct;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        try {
            $this->artisan('iam:sync-permissions', ['--assign-super-admin' => true]);
        } catch (\Throwable) {
        }
        app(BarcodeTemplateService::class)->ensureDefaults();
    }

    public function test_barcode_workspace_is_accessible(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('admin.barcode.index'))
            ->assertOk()
            ->assertSee(__('barcode::admin.title'));
    }

    public function test_barcode_search_returns_dto_keys_without_nested_product(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'BC-SEARCH-001');
        $product = $variant->product;
        $this->assertNotNull($product);

        $response = $this->actingAs($this->superAdmin())
            ->getJson(route('admin.barcode.search', ['q' => 'BC-SEARCH-001']))
            ->assertOk()
            ->assertJsonPath('data.0.sku', $variant->sku)
            ->assertJsonPath('data.0.variant_uuid', $variant->uuid)
            ->assertJsonPath('data.0.product_uuid', $product->uuid)
            ->assertJsonPath('data.0.product_name', $product->name)
            ->assertJsonPath('data.0.thumbnail_url', null);

        $this->assertIsString($response->json('data.0.owner_name'));
        $this->assertNotSame('', $response->json('data.0.owner_name'));

        $row = $response->json('data.0');
        $this->assertIsArray($row);
        $this->assertArrayHasKey('product_uuid', $row);
        $this->assertArrayHasKey('variant_uuid', $row);
        $this->assertArrayHasKey('sku', $row);
        $this->assertArrayHasKey('product_name', $row);
        $this->assertArrayHasKey('owner_name', $row);
        $this->assertArrayHasKey('thumbnail_url', $row);
        $this->assertArrayNotHasKey('product', $row);
    }

    public function test_barcode_search_without_media_returns_null_thumbnail(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'BC-NO-MEDIA-001');

        $this->actingAs($this->superAdmin())
            ->getJson(route('admin.barcode.search', ['q' => 'BC-NO-MEDIA-001']))
            ->assertOk()
            ->assertJsonPath('data.0.sku', $variant->sku)
            ->assertJsonPath('data.0.thumbnail_url', null);
    }

    public function test_barcode_exact_search_returns_single_match(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'BC-EXACT-001');

        $this->actingAs($this->superAdmin())
            ->getJson(route('admin.barcode.search', ['q' => 'BC-EXACT-001', 'exact' => 1]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sku', $variant->sku)
            ->assertJsonPath('data.0.variant_uuid', $variant->uuid);
    }

    public function test_barcode_exact_search_strips_scanner_control_characters(): void
    {
        $variant = $this->createPurchasableProduct(sku: '26300586');

        $this->actingAs($this->superAdmin())
            ->getJson(route('admin.barcode.search', ['q' => "26300586\r", 'exact' => 1]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sku', $variant->sku);
    }

    public function test_templates_are_seeded_and_listed(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('admin.barcode.templates.index'))
            ->assertOk()
            ->assertSee('A4 40 Labels');

        $this->assertEqualsCanonicalizing(
            ['a4_40', 'a4_24', 'a4_65', 'thermal_50x30'],
            BarcodeTemplate::query()->pluck('preset_code')->all(),
        );
        $this->assertDatabaseHas('barcode_templates', [
            'name' => 'A4 40 Labels',
            'preset_code' => 'a4_40',
            'is_default' => true,
        ]);
        $this->assertDatabaseMissing('barcode_templates', ['name' => 'Thermal 40×30']);
        $this->assertDatabaseMissing('barcode_templates', ['name' => 'A4 4×10']);
    }

    public function test_print_job_can_be_created_and_viewed(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'BC-PRINT-001');
        $template = BarcodeTemplate::query()->where('preset_code', 'a4_40')->where('is_default', true)->firstOrFail();
        $user = $this->superAdmin();

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
                    'label_width' => 999,
                    'paper_size' => 'thermal',
                ],
                'template_id' => $template->id,
            ])
            ->assertOk()
            ->assertJsonStructure(['job_uuid', 'print_url', 'pdf_url']);

        $job = BarcodePrintJob::query()->where('uuid', $response->json('job_uuid'))->first();
        $this->assertNotNull($job);
        $this->assertSame(2, $job->label_count);
        $this->assertSame('queued', $job->status);
        $this->assertEqualsWithDelta(48.5, (float) $job->settings['label_width'], 0.001);

        $this->actingAs($user)
            ->get(route('admin.barcode.print.show', $job))
            ->assertOk()
            ->assertSee($variant->sku)
            ->assertSee('bc-print-label--vertical', false);

        $job->refresh();
        $this->assertSame('printed', $job->status);

        $this->actingAs($user)
            ->get(route('admin.barcode.print.pdf', $job))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_print_page_uses_template_label_style_not_posted_settings(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'BC-STYLE-001');
        $template = BarcodeTemplate::query()->where('preset_code', 'a4_40')->firstOrFail();
        $user = $this->superAdmin();

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
                    'label_padding_top' => 2,
                    'label_padding_right' => 3,
                    'label_padding_bottom' => 2,
                    'label_padding_left' => 3,
                    'label_content_gap' => 1,
                    'label_owner_font_size' => 8,
                    'label_sku_font_size' => 7,
                    'label_width' => 999,
                ],
                'template_id' => $template->id,
            ])
            ->assertOk();

        $job = BarcodePrintJob::query()->where('uuid', $response->json('job_uuid'))->firstOrFail();
        $this->assertEqualsWithDelta(48.5, (float) $job->settings['label_width'], 0.001);

        $this->actingAs($user)
            ->get(route('admin.barcode.print.show', $job))
            ->assertOk()
            ->assertSee('padding: 1mm 2mm 1mm 2mm', false)
            ->assertSee('gap: 0.2mm', false)
            ->assertSee('font-size: 6pt', false);
    }

    public function test_history_lists_print_jobs(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'BC-HIST-001');
        $template = BarcodeTemplate::query()->where('preset_code', 'a4_40')->firstOrFail();

        $this->actingAs($this->superAdmin())
            ->postJson(route('admin.barcode.print.store'), $this->printPayload($variant->sku, $template, [
                'variant_uuid' => $variant->uuid,
                'product_name' => 'History Product',
            ]))
            ->assertOk();

        $this->actingAs($this->superAdmin())
            ->get(route('admin.barcode.history.index'))
            ->assertOk()
            ->assertSee(__('barcode::admin.history.title'))
            ->assertSee('Super Admin');
    }

    public function test_reprint_endpoint_renders_same_job_html(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'BC-REPRINT-001');
        $template = BarcodeTemplate::query()->where('preset_code', 'a4_40')->firstOrFail();

        $create = $this->actingAs($this->superAdmin())
            ->postJson(route('admin.barcode.print.store'), $this->printPayload($variant->sku, $template, [
                'variant_uuid' => $variant->uuid,
                'product_name' => 'Reprint Product',
                'quantity' => 3,
            ]))
            ->assertOk();

        $job = BarcodePrintJob::query()->where('uuid', $create->json('job_uuid'))->firstOrFail();
        $payload = $job->payload;
        $settings = $job->settings;

        $this->actingAs($this->superAdmin())
            ->get(route('admin.barcode.history.reprint', $job))
            ->assertOk()
            ->assertSee($variant->sku, false)
            ->assertSee('Reprint Product', false);

        $this->assertSame(1, BarcodePrintJob::query()->count());
        $job->refresh();
        $this->assertSame($payload, $job->payload);
        $this->assertSame($settings, $job->settings);
    }

    public function test_manual_barcode_print_job_can_be_created_without_product(): void
    {
        $template = BarcodeTemplate::query()->where('preset_code', 'a4_40')->where('is_default', true)->firstOrFail();
        $user = $this->superAdmin();

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
                    'label_width' => 999,
                ],
                'template_id' => $template->id,
            ])
            ->assertOk()
            ->assertJsonStructure(['job_uuid', 'print_url', 'pdf_url']);

        $job = BarcodePrintJob::query()->where('uuid', $response->json('job_uuid'))->first();
        $this->assertNotNull($job);
        $this->assertSame(3, $job->label_count);
        $this->assertEqualsWithDelta(48.5, (float) $job->settings['label_width'], 0.001);

        $this->actingAs($user)
            ->get(route('admin.barcode.print.show', $job))
            ->assertOk()
            ->assertSee('DISPLAY-SKU', false);

        $this->actingAs($user)
            ->get(route('admin.barcode.print.pdf', $job))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_manual_barcode_reprint_renders_same_job_html(): void
    {
        $template = BarcodeTemplate::query()->where('preset_code', 'a4_40')->firstOrFail();
        $user = $this->superAdmin();

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
                    'label_width' => 999,
                ],
                'template_id' => $template->id,
            ])
            ->assertOk();

        $job = BarcodePrintJob::query()->where('uuid', $create->json('job_uuid'))->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.barcode.history.reprint', $job))
            ->assertOk()
            ->assertSee('MANUAL-REPRINT-001', false)
            ->assertSee('Manual Reprint Label', false);
    }

    public function test_legacy_print_payload_is_normalized_on_store(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'BC-LEGACY-001');
        $template = BarcodeTemplate::query()->where('preset_code', 'a4_40')->firstOrFail();
        $user = $this->superAdmin();

        $response = $this->actingAs($user)
            ->postJson(route('admin.barcode.print.store'), $this->printPayload($variant->sku, $template, [
                'variant_uuid' => $variant->uuid,
                'product_name' => 'Legacy Product',
            ]))
            ->assertOk();

        $job = BarcodePrintJob::query()->where('uuid', $response->json('job_uuid'))->firstOrFail();

        $this->assertSame('PRODUCT', $job->payload['lines'][0]['source']);
        $this->assertSame($variant->sku, $job->payload['lines'][0]['barcode']);
    }

    public function test_barcode_generate_endpoint_returns_value(): void
    {
        $this->actingAs($this->superAdmin())
            ->getJson(route('admin.barcode.generate', ['strategy' => 'random']))
            ->assertOk()
            ->assertJsonStructure(['barcode', 'strategy']);
    }

    public function test_barcode_generate_numeric_sequence_returns_barcodes(): void
    {
        $this->actingAs($this->superAdmin())
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
        $this->actingAs($this->superAdmin())
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
        $template = BarcodeTemplate::query()->where('preset_code', 'a4_40')->firstOrFail();
        $user = $this->superAdmin();

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
                'template_id' => $template->id,
            ])
            ->assertOk();

        $job = BarcodePrintJob::query()->where('uuid', $response->json('job_uuid'))->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.barcode.print.show', $job))
            ->assertOk()
            ->assertSee('POR STORE', false)
            ->assertSee('1000019', false);
    }

    public function test_print_still_creates_job_when_owner_falls_back_to_store(): void
    {
        config(['app.name' => '']);
        $template = BarcodeTemplate::query()->where('preset_code', 'a4_40')->firstOrFail();

        $response = $this->actingAs($this->superAdmin())
            ->postJson(route('admin.barcode.print.store'), [
                'lines' => [[
                    'source' => 'MANUAL',
                    'title' => 'Fallback Label',
                    'barcode' => 'FALLBACK-001',
                    'display_text' => 'FALLBACK-001',
                    'owner_name' => 'Store',
                    'quantity' => 1,
                ]],
                'template_id' => $template->id,
            ])
            ->assertOk();

        $this->assertNotNull(BarcodePrintJob::query()->where('uuid', $response->json('job_uuid'))->first());
    }

    public function test_barcode_workspace_shows_manual_barcode_entry(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('admin.barcode.index'))
            ->assertOk()
            ->assertSee(__('barcode::admin.search.mode_manual'))
            ->assertSee(__('barcode::admin.manual.title'));
    }

    public function test_operator_can_use_center_search_print_and_history_but_not_templates(): void
    {
        $operator = $this->operatorUser();
        $variant = $this->createPurchasableProduct(sku: 'BC-OP-001');
        $template = BarcodeTemplate::query()->where('preset_code', 'a4_40')->firstOrFail();

        $this->actingAs($operator)
            ->get(route('admin.barcode.index'))
            ->assertOk();

        $this->actingAs($operator)
            ->getJson(route('admin.barcode.search', ['q' => 'BC-OP-001']))
            ->assertOk()
            ->assertJsonPath('data.0.sku', $variant->sku);

        $this->actingAs($operator)
            ->postJson(route('admin.barcode.print.store'), $this->printPayload($variant->sku, $template, [
                'variant_uuid' => $variant->uuid,
                'product_name' => 'Operator Product',
            ]))
            ->assertOk();

        $this->actingAs($operator)
            ->get(route('admin.barcode.history.index'))
            ->assertOk();

        $this->actingAs($operator)
            ->get(route('admin.barcode.templates.index'))
            ->assertForbidden();

        $this->actingAs($operator)
            ->post(route('admin.barcode.templates.store'), [
                'name' => 'Blocked',
                'preset_code' => 'a4_40',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_manage_templates_and_reprint(): void
    {
        $admin = $this->barcodeAdminUser();
        $template = BarcodeTemplate::query()->where('preset_code', 'a4_40')->firstOrFail();

        $create = $this->actingAs($admin)
            ->postJson(route('admin.barcode.print.store'), [
                'lines' => [[
                    'source' => 'MANUAL',
                    'title' => 'Admin Reprint',
                    'barcode' => 'ADMIN-REPRINT-001',
                    'display_text' => 'ADMIN-REPRINT-001',
                    'owner_name' => 'Acme Store',
                    'quantity' => 1,
                ]],
                'template_id' => $template->id,
            ])
            ->assertOk();

        $job = BarcodePrintJob::query()->where('uuid', $create->json('job_uuid'))->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.barcode.templates.index'))
            ->assertOk()
            ->assertSee('A4 40 Labels');

        $this->actingAs($admin)
            ->get(route('admin.barcode.templates.create'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.barcode.history.reprint', $job))
            ->assertOk()
            ->assertSee('ADMIN-REPRINT-001', false);
    }

    public function test_guest_and_user_without_barcode_permissions_cannot_access_center(): void
    {
        $this->get(route('admin.barcode.index'))
            ->assertRedirect();

        $this->actingAs($this->userWithoutBarcodePermissions())
            ->get(route('admin.barcode.index'))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function printPayload(string $sku, BarcodeTemplate $template, array $overrides = []): array
    {
        return [
            'template_id' => $template->id,
            'lines' => [[
                'sku' => $sku,
                'owner_name' => 'Acme Store',
                'product_name' => 'Test Product',
                'quantity' => 1,
                ...$overrides,
            ]],
            'settings' => [
                'label_width' => 999,
            ],
        ];
    }
}
