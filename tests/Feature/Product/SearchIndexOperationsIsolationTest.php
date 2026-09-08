<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use PHPUnit\Framework\TestCase;

final class SearchIndexOperationsIsolationTest extends TestCase
{
    public function test_shop_controller_passes_one_candidate_list_to_listing_and_facets(): void
    {
        $path = dirname(__DIR__, 3).'/modules/Cart/src/Http/Controllers/ShopController.php';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);

        $this->assertSame(1, substr_count($contents, 'candidateUuids('));
        $this->assertStringContainsString('buildFor($filters, $searchUuids)', $contents);
        $this->assertStringContainsString('searchUuids: $searchUuids', $contents);
    }
}
