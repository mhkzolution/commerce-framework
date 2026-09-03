<?php

declare(strict_types=1);

namespace Tests\Unit\Barcode;

use Commerce\Barcode\DTO\ResolvedBarcodeLayout;
use Commerce\Barcode\Services\BarcodeLayoutCalculator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class BarcodeLayoutCalculatorTest extends TestCase
{
    #[Test]
    public function a4_40_snapshot_resolves_forty_cells_inside_a4_paper(): void
    {
        $layout = (new BarcodeLayoutCalculator)->resolve($this->a4_40Snapshot(), 40);

        $this->assertInstanceOf(ResolvedBarcodeLayout::class, $layout);
        $this->assertSame(40, $layout->labelsPerPage);
        $this->assertSame(10, $layout->rows);
        $this->assertSame(4, $layout->columns);
        $this->assertSame(210.0, $layout->paperWidthMm);
        $this->assertSame(297.0, $layout->paperHeightMm);
        $this->assertCount(40, $layout->cells);
        $this->assertCellsStayInsidePaper($layout);
    }

    #[Test]
    public function a4_65_snapshot_resolves_sixty_five_cells_inside_paper(): void
    {
        $layout = (new BarcodeLayoutCalculator)->resolve($this->a4_65Snapshot(), 65);

        $this->assertInstanceOf(ResolvedBarcodeLayout::class, $layout);
        $this->assertSame(65, $layout->labelsPerPage);
        $this->assertSame(13, $layout->rows);
        $this->assertSame(5, $layout->columns);
        $this->assertSame(210.0, $layout->paperWidthMm);
        $this->assertSame(297.0, $layout->paperHeightMm);
        $this->assertCount(65, $layout->cells);
        $this->assertCellsStayInsidePaper($layout);
    }

    #[Test]
    public function calculator_and_dto_source_files_do_not_reference_product(): void
    {
        $calculator = file_get_contents(base_path('modules/Barcode/src/Services/BarcodeLayoutCalculator.php'));
        $dto = file_get_contents(base_path('modules/Barcode/src/DTO/ResolvedBarcodeLayout.php'));

        $this->assertNotFalse($calculator);
        $this->assertNotFalse($dto);
        $this->assertStringNotContainsString('Commerce\\Product', $calculator);
        $this->assertStringNotContainsString('Commerce\\Product', $dto);
    }

    /**
     * @return array<string, mixed>
     */
    private function a4_40Snapshot(): array
    {
        return [
            'preset_code' => 'a4_40',
            'paper_size' => 'a4',
            'paper_width_mm' => 210,
            'paper_height_mm' => 297,
            'rows' => 10,
            'columns' => 4,
            'label_width' => 48.5,
            'label_height' => 25.4,
            'spacing_horizontal' => 2,
            'spacing_vertical' => 2,
            'margin_top' => 12.5,
            'margin_right' => 5,
            'margin_bottom' => 12.5,
            'margin_left' => 5,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function a4_65Snapshot(): array
    {
        return [
            'preset_code' => 'a4_65',
            'paper_size' => 'a4',
            'paper_width_mm' => 210,
            'paper_height_mm' => 297,
            'rows' => 13,
            'columns' => 5,
            'label_width' => 38.1,
            'label_height' => 21.2,
            'spacing_horizontal' => 0,
            'spacing_vertical' => 0,
            'margin_top' => 10.7,
            'margin_right' => 9.75,
            'margin_bottom' => 10.7,
            'margin_left' => 9.75,
        ];
    }

    private function assertCellsStayInsidePaper(ResolvedBarcodeLayout $layout): void
    {
        foreach ($layout->cells as $cell) {
            $this->assertGreaterThanOrEqual(0.0, $cell['left']);
            $this->assertGreaterThanOrEqual(0.0, $cell['top']);
            $this->assertLessThanOrEqual($layout->paperWidthMm + 0.0001, $cell['left'] + $cell['width']);
            $this->assertLessThanOrEqual($layout->paperHeightMm + 0.0001, $cell['top'] + $cell['height']);
        }
    }
}
