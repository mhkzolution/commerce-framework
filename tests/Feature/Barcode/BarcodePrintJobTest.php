<?php

declare(strict_types=1);

namespace Tests\Feature\Barcode;

use Commerce\Barcode\Http\Controllers\Admin\HistoryController;
use Commerce\Barcode\Http\Requests\StoreBarcodePrintRequest;
use Commerce\Barcode\Models\BarcodePrintJob;
use Commerce\Barcode\Models\BarcodeTemplate;
use Commerce\Barcode\Services\BarcodeLabelExpansionService;
use Commerce\Barcode\Services\BarcodeLabelRenderer;
use Commerce\Barcode\Services\BarcodeLayoutCalculator;
use Commerce\Barcode\Services\BarcodePrintJobService;
use Commerce\Barcode\Services\BarcodePrintService;
use Commerce\Barcode\Services\BarcodeTemplateService;
use Commerce\Iam\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesPurchasableProduct;

final class BarcodePrintJobTest extends BarcodeFeatureTestCase
{
    use CreatesPurchasableProduct;


    /**
     * @var list<string>
     */
    private const SNAPSHOT_KEYS = [
        'preset_code',
        'paper_size',
        'paper_width_mm',
        'paper_height_mm',
        'rows',
        'columns',
        'label_width',
        'label_height',
        'margin_top',
        'margin_right',
        'margin_bottom',
        'margin_left',
        'spacing_horizontal',
        'spacing_vertical',
        'show_name',
        'show_sku',
        'show_owner',
        'show_barcode',
        'label_padding_top',
        'label_padding_right',
        'label_padding_bottom',
        'label_padding_left',
        'label_content_gap',
        'label_sku_font_size',
        'label_owner_font_size',
        'label_orientation',
        'name',
        'id',
    ];

    private BarcodeTemplateService $templates;

    private BarcodePrintJobService $printJobs;

    private BarcodePrintService $printService;

    protected function setUp(): void
    {
        parent::setUp();

        $moduleConfig = require base_path('modules/Barcode/config/barcode.php');
        config()->set('barcode', array_replace_recursive((array) config('barcode', []), $moduleConfig));

        $migrator = $this->app->make('migrator');
        if (! $migrator->repositoryExists()) {
            $migrator->getRepository()->createRepository();
        }
        $migrator->run([base_path('modules/Barcode/database/migrations')]);

        $this->app['view']->addNamespace('barcode', base_path('modules/Barcode/resources/views'));
        $this->app['translator']->addNamespace('barcode', base_path('modules/Barcode/resources/lang'));

        if (! Route::has('admin.barcode.index')) {
            Route::get('/admin/barcode', static fn () => '')->name('admin.barcode.index');
        }
        if (! Route::has('admin.barcode.print.pdf')) {
            Route::get('/admin/barcode/print/{job}/pdf', static fn () => '')->name('admin.barcode.print.pdf');
        }
        $this->app['router']->getRoutes()->refreshNameLookups();

        $this->app->instance(BarcodeLabelExpansionService::class, new class
        {
            /**
             * @param  list<array<string, mixed>>  $lines
             * @return list<array<string, mixed>>
             */
            public function expand(array $lines): array
            {
                $labels = [];

                foreach ($lines as $line) {
                    $quantity = max(1, (int) ($line['quantity'] ?? 1));
                    $title = (string) ($line['title'] ?? $line['product_name'] ?? '');
                    $label = [
                        'owner_name' => (string) ($line['owner_name'] ?? ''),
                        'barcode' => (string) ($line['barcode'] ?? $line['sku'] ?? ''),
                        'display_text' => (string) ($line['display_text'] ?? $line['sku'] ?? ''),
                        'title' => $title,
                        'product_name' => $title,
                    ];

                    for ($i = 0; $i < $quantity; $i++) {
                        $labels[] = $label;
                    }
                }

                return $labels;
            }
        });

        $this->templates = new BarcodeTemplateService;
        $this->printJobs = new BarcodePrintJobService;
        $this->printService = new BarcodePrintService(
            new BarcodeLayoutCalculator,
            new BarcodeLabelRenderer,
            $this->printJobs,
        );
    }

    #[Test]
    public function print_create_persists_template_snapshot_not_client_millimetres(): void
    {
        $template = $this->templates->create([
            'name' => 'Snapshot Source',
            'preset_code' => 'a4_40',
        ]);

        $job = $this->printJobs->create($this->oneLine(), $template, 0);

        foreach (self::SNAPSHOT_KEYS as $key) {
            $this->assertArrayHasKey($key, $job->settings ?? []);
        }

        $this->assertSame('a4_40', $job->settings['preset_code']);
        $this->assertSame(4, $job->settings['columns']);
        $this->assertSame(210, $job->settings['paper_width_mm']);
        $this->assertIsNumeric($job->settings['paper_width_mm']);
        $this->assertIsNumeric($job->settings['paper_height_mm']);
        $this->assertEqualsWithDelta(48.5, (float) $job->settings['label_width'], 0.001);
        $this->assertSame($template->id, $job->settings['id']);
        $this->assertSame('Snapshot Source', $job->settings['name']);
        $this->assertSame(['lines' => $this->oneLine()], $job->payload);

        $rules = (new StoreBarcodePrintRequest)->rules();
        $this->assertArrayHasKey('template_id', $rules);
        $this->assertContains('required', $rules['template_id']);
        $this->assertArrayNotHasKey('settings', $rules);
        $this->assertArrayNotHasKey('settings.label_width', $rules);
        $this->assertArrayNotHasKey('settings.paper_size', $rules);
        $this->assertArrayNotHasKey('settings.show_owner', $rules);

        $validator = Validator::make([
            'template_id' => $template->id,
            'lines' => [$this->validLineInput()],
            'settings' => ['label_width' => 99, 'paper_size' => 'thermal', 'show_owner' => false],
        ], $rules);

        $this->assertTrue($validator->passes(), json_encode($validator->errors()->toArray()));
        $this->assertArrayNotHasKey('settings', $validator->validated());
    }

    #[Test]
    public function print_html_omits_owner_when_template_show_owner_is_false(): void
    {
        $template = $this->templates->create([
            'name' => 'No Owner',
            'preset_code' => 'a4_40',
            'show_owner' => false,
        ]);

        $job = $this->printJobs->create($this->oneLine('Hidden Owner LLC'), $template, 0);
        $html = $this->printService->printView($job)->render();

        $this->assertStringNotContainsString('Hidden Owner LLC', $html);
        $this->assertStringNotContainsString('bc-print-label__owner">', $html);
    }

    #[Test]
    public function reprint_keeps_snapshot_geometry_after_live_template_preset_change(): void
    {
        $template = $this->templates->create([
            'name' => 'Geometry Lock',
            'preset_code' => 'a4_40',
        ]);

        $job = $this->printJobs->create($this->oneLine(), $template, 0);

        $this->assertSame(4, $job->settings['columns']);
        $this->assertSame(210, $job->settings['paper_width_mm']);

        $this->templates->update($template, [
            'name' => 'Geometry Lock',
            'preset_code' => 'a4_24',
        ]);
        config()->set('barcode.presets.a4_40.label_width', 1);
        config()->set('barcode.presets.a4_40.columns', 1);

        $reprint = (new HistoryController($this->printJobs, $this->printService))->reprint($job);
        $this->assertInstanceOf(View::class, $reprint);

        $html = $reprint->render();
        $this->assertStringContainsString('48.5mm', $html);
        $this->assertStringContainsString('25.4mm', $html);
        $this->assertStringContainsString('left: 5mm', $html);
        $this->assertStringContainsString('top: 12.5mm', $html);
        $this->assertStringContainsString('width: 210mm', $html);
        $this->assertStringNotContainsString('63.5mm', $html);
        $this->assertStringNotContainsString('33.9mm', $html);

        $job->refresh();
        $layout = (new BarcodeLayoutCalculator)->resolve($job->settings ?? [], 1);
        $this->assertSame(4, $layout->columns);
        $this->assertSame(10, $layout->rows);
        $this->assertEqualsWithDelta(48.5, $layout->labelWidthMm, 0.001);
        $this->assertEqualsWithDelta(12.5, $layout->marginTopMm, 0.001);
        $this->assertEqualsWithDelta(5.0, $layout->marginLeftMm, 0.001);
    }

    #[Test]
    public function reprint_does_not_insert_or_mutate_the_original_job(): void
    {
        $template = $this->templates->create([
            'name' => 'Immutable Job',
            'preset_code' => 'a4_40',
        ]);

        $job = $this->printJobs->create($this->oneLine(), $template, 0);
        $settings = $job->settings;
        $payload = $job->payload;

        $this->assertSame(1, BarcodePrintJob::query()->count());

        $reprint = (new HistoryController($this->printJobs, $this->printService))->reprint($job);
        $this->assertInstanceOf(View::class, $reprint);
        $reprint->render();

        $this->assertSame(1, BarcodePrintJob::query()->count());

        $job->refresh();
        $this->assertSame($job->uuid, BarcodePrintJob::query()->sole()->uuid);
        $this->assertSame($settings, $job->settings);
        $this->assertSame($payload, $job->payload);

        $historyJs = file_get_contents(base_path('resources/js/barcode/history.js'));
        $this->assertNotFalse($historyJs);
        $this->assertStringNotContainsString('bc_reprint_payload', $historyJs);
        $this->assertStringNotContainsString('reprintPayload', $historyJs);
        $this->assertStringContainsString('window.location', $historyJs);
    }

    #[Test]
    public function print_service_source_does_not_contain_barcode_template(): void
    {
        $source = file_get_contents(base_path('modules/Barcode/src/Services/BarcodePrintService.php'));

        $this->assertNotFalse($source);
        $this->assertStringNotContainsString('BarcodeTemplate', $source);
        $this->assertStringContainsString('->resolve(', $source);
        $this->assertStringNotContainsString('->compute(', $source);
        $this->assertStringNotContainsString("config('barcode.presets')", $source);
    }

    #[Test]
    public function print_create_is_queued_then_printed_never_completed(): void
    {
        $template = $this->templates->create([
            'name' => 'Status Source',
            'preset_code' => 'a4_40',
        ]);

        $job = $this->printJobs->create($this->oneLine(), $template, 0);

        $this->assertSame('queued', $job->status);
        $this->assertNull($job->printed_at);
        $this->assertNotSame('completed', $job->status);

        $this->printService->printView($job)->render();

        $job->refresh();
        $this->assertSame('printed', $job->status);
        $this->assertNotNull($job->printed_at);
        $this->assertNotSame('completed', $job->status);
        $this->assertDatabaseMissing('barcode_print_jobs', ['status' => 'completed']);
    }

    #[Test]
    public function renderer_exception_marks_job_failed(): void
    {
        $template = $this->templates->create([
            'name' => 'Fail Source',
            'preset_code' => 'a4_40',
        ]);

        $job = $this->printJobs->create($this->oneLine(), $template, 0);

        $this->app->instance(BarcodeLabelExpansionService::class, new class
        {
            public function expand(array $lines): array
            {
                throw new \RuntimeException('svg failed');
            }
        });

        $printService = new BarcodePrintService(
            new BarcodeLayoutCalculator,
            new BarcodeLabelRenderer,
        );

        try {
            $printService->printView($job);
            $this->fail('Renderer exception should surface');
        } catch (\RuntimeException $exception) {
            $this->assertSame('svg failed', $exception->getMessage());
        }

        $job->refresh();
        $this->assertSame('failed', $job->status);
        $this->assertNotSame('completed', $job->status);
    }

    #[Test]
    public function reprint_after_product_name_change_shows_stored_product_name(): void
    {
        $this->useLiveExpansionService();

        $variant = $this->createPurchasableProduct(sku: 'BC-RENAME-001');
        $product = $variant->product;
        $this->assertNotNull($product);
        $storedName = $product->name;

        $template = $this->templates->create([
            'name' => 'Rename Lock',
            'preset_code' => 'a4_40',
        ]);

        $job = $this->printJobs->create([[
            'source' => 'PRODUCT',
            'title' => $storedName,
            'barcode' => $variant->sku,
            'display_text' => $variant->sku,
            'owner_name' => 'Acme Store',
            'product_name' => $storedName,
            'variant_uuid' => $variant->uuid,
            'quantity' => 1,
        ]], $template, 0);

        $product->forceFill(['name' => 'Renamed After Print'])->save();
        $this->assertSame('Renamed After Print', $product->fresh()->name);

        $reprint = (new HistoryController($this->printJobs, $this->printService))->reprint($job);
        $this->assertInstanceOf(View::class, $reprint);

        $html = $reprint->render();
        $this->assertStringContainsString($storedName, $html);
        $this->assertStringNotContainsString('Renamed After Print', $html);
    }

    #[Test]
    public function reprint_after_product_deleted_still_succeeds_from_payload(): void
    {
        $this->useLiveExpansionService();

        $variant = $this->createPurchasableProduct(sku: 'BC-DELETE-001');
        $product = $variant->product;
        $this->assertNotNull($product);
        $storedName = $product->name;
        $productId = $product->id;

        $template = $this->templates->create([
            'name' => 'Delete Lock',
            'preset_code' => 'a4_40',
        ]);

        $job = $this->printJobs->create([[
            'source' => 'PRODUCT',
            'title' => $storedName,
            'barcode' => $variant->sku,
            'display_text' => $variant->sku,
            'owner_name' => 'Acme Store',
            'product_name' => $storedName,
            'variant_uuid' => $variant->uuid,
            'quantity' => 1,
        ]], $template, 0);

        $product->forceDelete();
        $this->assertDatabaseMissing('products', ['id' => $productId]);

        $reprint = (new HistoryController($this->printJobs, $this->printService))->reprint($job);
        $this->assertInstanceOf(View::class, $reprint);

        $html = $reprint->render();
        $this->assertStringContainsString($storedName, $html);
        $this->assertStringContainsString('BC-DELETE-001', $html);
    }

    #[Test]
    public function http_print_store_ignores_browser_label_width(): void
    {
        $this->templates->ensureDefaults();

        $template = BarcodeTemplate::query()->where('preset_code', 'a4_40')->firstOrFail();
        $this->assertEqualsWithDelta(48.5, (float) $template->label_width, 0.001);

        $user = new User;
        $user->id = 1;
        $user->name = 'Printer';

        $response = $this->withoutMiddleware()
            ->actingAs($user)
            ->postJson(route('admin.barcode.print.store'), [
                'template_id' => $template->id,
                'lines' => [$this->validLineInput()],
                'settings' => [
                    'label_width' => 999,
                    'paper_size' => 'thermal',
                ],
            ]);

        $response->assertOk();

        $job = BarcodePrintJob::query()->where('uuid', $response->json('job_uuid'))->firstOrFail();
        $this->assertEqualsWithDelta(48.5, (float) $job->settings['label_width'], 0.001);
        $this->assertNotEquals(999, $job->settings['label_width']);
        $this->assertSame('queued', $job->status);
    }

    #[Test]
    public function renderer_succeeds_after_template_row_is_deleted(): void
    {
        $template = $this->templates->create([
            'name' => 'Disposable Layout',
            'preset_code' => 'a4_40',
        ]);

        $job = $this->printJobs->create($this->oneLine(), $template, 0);
        $settings = $job->settings;

        BarcodeTemplate::query()->whereKey($template->id)->delete();
        $this->assertDatabaseMissing('barcode_templates', ['id' => $template->id]);

        $html = $this->printService->printView($job)->render();

        $this->assertStringContainsString('48.5mm', $html);
        $this->assertStringContainsString('25.4mm', $html);

        $job->refresh();
        $this->assertSame($settings, $job->settings);
        $this->assertSame('printed', $job->status);
    }

    #[Test]
    public function reprint_survives_catalog_millimetre_mutation(): void
    {
        $template = $this->templates->create([
            'name' => 'Catalog Lock',
            'preset_code' => 'a4_40',
        ]);

        $job = $this->printJobs->create($this->oneLine(), $template, 0);

        config()->set('barcode.presets.a4_40.label_width', 1);
        config()->set('barcode.presets.a4_40.label_height', 1);
        config()->set('barcode.presets.a4_40.columns', 1);

        $reprint = (new HistoryController($this->printJobs, $this->printService))->reprint($job);
        $this->assertInstanceOf(View::class, $reprint);

        $html = $reprint->render();
        $this->assertStringContainsString('48.5mm', $html);
        $this->assertStringContainsString('25.4mm', $html);
        $this->assertEqualsWithDelta(48.5, (float) $job->fresh()->settings['label_width'], 0.001);
    }

    private function useLiveExpansionService(): void
    {
        $this->app->forgetInstance(BarcodeLabelExpansionService::class);
        $this->app->offsetUnset(BarcodeLabelExpansionService::class);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function oneLine(string $ownerName = 'Acme Store'): array
    {
        return [[
            'source' => 'MANUAL',
            'title' => 'Widget',
            'barcode' => 'SKU-001',
            'display_text' => 'SKU-001',
            'owner_name' => $ownerName,
            'quantity' => 1,
        ]];
    }

    /**
     * @return array<string, mixed>
     */
    private function validLineInput(): array
    {
        return [
            'title' => 'Widget',
            'barcode' => 'SKU-001',
            'owner_name' => 'Acme Store',
            'quantity' => 1,
        ];
    }
}
