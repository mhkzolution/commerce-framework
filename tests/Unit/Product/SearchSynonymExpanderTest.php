<?php

declare(strict_types=1);

namespace Tests\Unit\Product;

use Carbon\CarbonImmutable;
use Commerce\Core\Models\SearchDocument;
use Commerce\Product\Models\SearchSynonym;
use Commerce\Product\Services\SearchSynonymExpander;
use Commerce\Product\Support\SearchNormalizer;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SearchSynonymExpanderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_it_replaces_matching_tokens_in_the_configured_direction_only(): void
    {
        $synonym = SearchSynonym::query()->create([
            'from_term' => ' ผ้าฝ้าย ',
            'to_term' => ' Cotton ',
        ]);

        $this->assertSame('ผ้าฝ้าย', $synonym->from_term);
        $this->assertSame('cotton', $synonym->to_term);

        app()->forgetInstance(SearchSynonymExpander::class);
        $expander = app(SearchSynonymExpander::class);

        $this->assertSame(['cotton', 'tee'], $expander->expand(['ผ้าฝ้าย', 'tee']));
        $this->assertSame(['cotton'], $expander->expand(['cotton']));
    }

    public function test_from_term_is_unique_after_normalization(): void
    {
        SearchSynonym::query()->create([
            'from_term' => ' Cotton ',
            'to_term' => 'ผ้าฝ้าย',
        ]);

        $this->expectException(QueryException::class);

        SearchSynonym::query()->create([
            'from_term' => '  COTTON  ',
            'to_term' => 'เสื้อยืด',
        ]);
    }

    public function test_creating_or_updating_a_synonym_does_not_touch_search_documents(): void
    {
        CarbonImmutable::setTestNow('2026-09-08 08:00:00');

        $document = SearchDocument::query()->create([
            'index_name' => 'products',
            'document_id' => 'dummy-product',
            'title' => 'Dummy product',
            'body' => 'Dummy product body',
            'payload' => [],
        ]);
        $originalUpdatedAt = $document->updated_at;

        CarbonImmutable::setTestNow('2026-09-08 09:00:00');

        $synonym = SearchSynonym::query()->create([
            'from_term' => SearchNormalizer::textNormalize('ผ้าฝ้าย'),
            'to_term' => SearchNormalizer::textNormalize('cotton'),
        ]);
        $synonym->update([
            'to_term' => SearchNormalizer::textNormalize('cotton fabric'),
        ]);

        $this->assertTrue($originalUpdatedAt->equalTo($document->fresh()->updated_at));
    }
}
