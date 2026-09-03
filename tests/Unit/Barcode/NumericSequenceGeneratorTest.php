<?php

declare(strict_types=1);

namespace Tests\Unit\Barcode;

use Commerce\Core\Barcode\NumericSequenceGenerator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class NumericSequenceGeneratorTest extends TestCase
{
    private NumericSequenceGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new NumericSequenceGenerator;
    }

    public function test_generates_sequential_barcodes_from_start(): void
    {
        $barcodes = $this->generator->generate('15202104', 20);

        $this->assertCount(20, $barcodes);
        $this->assertSame('15202104', $barcodes[0]);
        $this->assertSame('15202123', $barcodes[19]);
    }

    public function test_preserves_leading_zeros_when_incrementing_within_padding(): void
    {
        $barcodes = $this->generator->generate('0009', 3);

        $this->assertSame(['0009', '0010', '0011'], $barcodes);
    }

    public function test_extends_length_when_overflowing_padding(): void
    {
        $barcodes = $this->generator->generate('99', 3);

        $this->assertSame(['99', '100', '101'], $barcodes);
    }

    #[DataProvider('invalidStartProvider')]
    public function test_rejects_invalid_start(string $start): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->generator->generate($start, 1);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidStartProvider(): array
    {
        return [
            'empty' => [''],
            'alpha' => ['ABC123'],
            'mixed' => ['12A34'],
            'whitespace only' => ['   '],
        ];
    }

    public function test_rejects_invalid_count(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->generator->generate('100', 0);
    }
}
