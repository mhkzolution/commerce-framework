<?php

declare(strict_types=1);

namespace Tests\Feature\Search;

use Commerce\Contracts\Search\SearchIndexInterface;
use Commerce\Contracts\Search\SearchQueryInterface;
use Commerce\Core\Search\DatabaseSearchIndex;
use Commerce\Core\Search\DatabaseSearchQuery;
use Commerce\Core\Search\ElasticsearchSearchIndex;
use Commerce\Core\Search\ElasticsearchSearchQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SearchDriverTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_driver_is_bound_by_default(): void
    {
        $this->assertInstanceOf(DatabaseSearchIndex::class, app(SearchIndexInterface::class));
        $this->assertInstanceOf(DatabaseSearchQuery::class, app(SearchQueryInterface::class));
    }

    public function test_elasticsearch_driver_classes_are_available(): void
    {
        $this->assertTrue(class_exists(ElasticsearchSearchIndex::class));
        $this->assertTrue(class_exists(ElasticsearchSearchQuery::class));
        $this->assertSame('database', config('commerce.search.driver'));
    }
}
