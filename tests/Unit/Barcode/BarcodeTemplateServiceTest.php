<?php

declare(strict_types=1);

namespace Tests\Unit\Barcode;

use Commerce\Barcode\Models\BarcodeTemplate;
use Commerce\Barcode\Services\BarcodeTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class BarcodeTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    private BarcodeTemplateService $service;

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

        $this->service = new BarcodeTemplateService;
    }

    #[Test]
    public function ensure_defaults_seeds_exactly_four_presets_with_a4_40_default(): void
    {
        $this->service->ensureDefaults();

        $templates = BarcodeTemplate::query()->orderBy('id')->get();

        $this->assertCount(4, $templates);
        $this->assertSame(
            ['a4_40', 'a4_24', 'a4_65', 'thermal_50x30'],
            $templates->pluck('preset_code')->all(),
        );
        $this->assertSame('a4_40', $templates->firstWhere('is_default', true)?->preset_code);
        $this->assertFalse($templates->contains(fn (BarcodeTemplate $template): bool => $template->name === 'Thermal 40×30'));
    }

    #[Test]
    public function create_with_a4_40_ignores_client_paper_size_and_persists_catalog_millimetres(): void
    {
        $template = $this->service->create([
            'name' => 'Client override attempt',
            'preset_code' => 'a4_40',
            'paper_size' => 'thermal',
            'rows' => 1,
            'columns' => 1,
            'label_width' => 99,
            'label_height' => 99,
            'margin_top' => 0,
            'margin_right' => 0,
            'margin_bottom' => 0,
            'margin_left' => 0,
            'spacing_horizontal' => 9,
            'spacing_vertical' => 9,
        ]);

        $this->assertSame('a4_40', $template->preset_code);
        $this->assertSame('a4', $template->paper_size);
        $this->assertSame(10, $template->rows);
        $this->assertSame(4, $template->columns);
        $this->assertEqualsWithDelta(48.5, (float) $template->label_width, 0.001);
        $this->assertEqualsWithDelta(25.4, (float) $template->label_height, 0.001);
        $this->assertEqualsWithDelta(12.5, (float) $template->margin_top, 0.001);
        $this->assertEqualsWithDelta(5.0, (float) $template->margin_right, 0.001);
        $this->assertEqualsWithDelta(12.5, (float) $template->margin_bottom, 0.001);
        $this->assertEqualsWithDelta(5.0, (float) $template->margin_left, 0.001);
        $this->assertEqualsWithDelta(2.0, (float) $template->spacing_horizontal, 0.001);
        $this->assertEqualsWithDelta(2.0, (float) $template->spacing_vertical, 0.001);
    }

    #[Test]
    public function updating_preset_code_overwrites_frozen_geometry_from_catalog(): void
    {
        $template = $this->service->create([
            'name' => 'Switchable',
            'preset_code' => 'a4_40',
            'show_owner' => false,
            'label_sku_font_size' => 8,
            'label_orientation' => 'horizontal',
        ]);

        $updated = $this->service->update($template, [
            'name' => 'Switchable',
            'preset_code' => 'a4_24',
            'show_owner' => false,
            'label_sku_font_size' => 8,
            'label_orientation' => 'horizontal',
            'paper_size' => 'thermal',
            'rows' => 99,
            'columns' => 99,
        ]);

        $this->assertSame('a4_24', $updated->preset_code);
        $this->assertSame('a4', $updated->paper_size);
        $this->assertSame(8, $updated->rows);
        $this->assertSame(3, $updated->columns);
        $this->assertEqualsWithDelta(63.5, (float) $updated->label_width, 0.001);
        $this->assertEqualsWithDelta(33.9, (float) $updated->label_height, 0.001);
        $this->assertEqualsWithDelta(5.9, (float) $updated->margin_top, 0.001);
        $this->assertEqualsWithDelta(7.75, (float) $updated->margin_right, 0.001);
        $this->assertEqualsWithDelta(5.9, (float) $updated->margin_bottom, 0.001);
        $this->assertEqualsWithDelta(7.75, (float) $updated->margin_left, 0.001);
        $this->assertEqualsWithDelta(2.0, (float) $updated->spacing_horizontal, 0.001);
        $this->assertEqualsWithDelta(2.0, (float) $updated->spacing_vertical, 0.001);
        $this->assertFalse($updated->show_owner);
        $this->assertEqualsWithDelta(8.0, (float) $updated->label_sku_font_size, 0.001);
        $this->assertSame('horizontal', $updated->label_orientation);
        $this->assertSame('Switchable', $updated->name);
    }
}
