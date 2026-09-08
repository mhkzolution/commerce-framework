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

    public function test_label_reindex_uses_indexer_relation_constant(): void
    {
        $indexer = file_get_contents(dirname(__DIR__, 3).'/modules/Product/src/Services/ProductSearchIndexer.php');
        $service = file_get_contents(dirname(__DIR__, 3).'/modules/Catalog/src/Services/AttributeValueService.php');
        $this->assertNotFalse($indexer);
        $this->assertNotFalse($service);

        $this->assertStringContainsString('INDEX_RELATIONS', $indexer);
        $this->assertStringContainsString('with(self::INDEX_RELATIONS)', $indexer);
        $this->assertStringContainsString('ProductSearchIndexer::INDEX_RELATIONS', $service);
        $this->assertStringNotContainsString(
            "with(['variants', 'categories', 'brand', 'attributeValues.attributeValue'])",
            $service,
        );
    }
}
