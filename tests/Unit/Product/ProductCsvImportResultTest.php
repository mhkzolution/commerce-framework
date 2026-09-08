<?php

declare(strict_types=1);

namespace Tests\Unit\Product;

use Commerce\Product\Import\ProductCsvImportResult;
use Tests\TestCase;

final class ProductCsvImportResultTest extends TestCase
{
    public function test_with_message_appends_message_and_does_not_change_counters(): void
    {
        $original = new ProductCsvImportResult(
            created: 2,
            updated: 3,
            skipped: 4,
            duplicates: 5,
            linkedImages: 6,
            messages: ['existing'],
            duplicateSkus: ['SKU-1'],
            errors: ['err'],
        );

        $next = $original->withMessage('Row 1: skipped reserved attribute column "Brand" (code "brand").');

        $this->assertSame(2, $next->created);
        $this->assertSame(3, $next->updated);
        $this->assertSame(4, $next->skipped);
        $this->assertSame(5, $next->duplicates);
        $this->assertSame(6, $next->linkedImages);
        $this->assertSame(['SKU-1'], $next->duplicateSkus);
        $this->assertSame(['err'], $next->errors);
        $this->assertSame(
            [
                'existing',
                'Row 1: skipped reserved attribute column "Brand" (code "brand").',
            ],
            $next->messages,
        );

        $this->assertSame(2, $original->created);
        $this->assertSame(['existing'], $original->messages);
        $this->assertSame(2 + 3 + 4 + 5, $next->totalProcessed());
    }
}
