<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Carbon\CarbonImmutable;
use Commerce\Core\Models\SearchDocument;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Product\Models\SearchSynonym;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SearchSynonymAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IamSeeder::class);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_staff_can_view_create_update_and_delete_search_synonyms_without_reindexing(): void
    {
        $user = User::query()->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.catalog.search-synonyms.index'))
            ->assertOk();

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
        $this->actingAs($user)
            ->post(route('admin.catalog.search-synonyms.store'), [
                'from_term' => ' ผ้าฝ้าย ',
                'to_term' => ' Cotton ',
            ])
            ->assertRedirect(route('admin.catalog.search-synonyms.index'));

        $synonym = SearchSynonym::query()->firstOrFail();
        $this->assertSame('ผ้าฝ้าย', $synonym->from_term);
        $this->assertSame('cotton', $synonym->to_term);
        $this->assertTrue($originalUpdatedAt->equalTo($document->fresh()->updated_at));

        CarbonImmutable::setTestNow('2026-09-08 10:00:00');
        $this->actingAs($user)
            ->put(route('admin.catalog.search-synonyms.update', $synonym->id), [
                'from_term' => 'ผ้าฝ้าย',
                'to_term' => ' cotton fabric ',
            ])
            ->assertRedirect(route('admin.catalog.search-synonyms.index'));

        $this->assertSame('cotton fabric', $synonym->fresh()->to_term);
        $this->assertTrue($originalUpdatedAt->equalTo($document->fresh()->updated_at));

        CarbonImmutable::setTestNow('2026-09-08 11:00:00');
        $this->actingAs($user)
            ->delete(route('admin.catalog.search-synonyms.destroy', $synonym->id))
            ->assertRedirect(route('admin.catalog.search-synonyms.index'));

        $this->assertDatabaseMissing('product_search_synonyms', ['id' => $synonym->id]);
        $this->assertTrue($originalUpdatedAt->equalTo($document->fresh()->updated_at));
    }

    public function test_normalized_from_term_must_be_unique(): void
    {
        $user = User::query()->firstOrFail();

        SearchSynonym::query()->create([
            'from_term' => 'cotton',
            'to_term' => 'ผ้าฝ้าย',
        ]);

        $this->actingAs($user)
            ->post(route('admin.catalog.search-synonyms.store'), [
                'from_term' => '  COTTON  ',
                'to_term' => 'fabric',
            ])
            ->assertInvalid('from_term');

        $this->assertSame(1, SearchSynonym::query()->count());
    }

    public function test_non_string_terms_are_rejected_without_type_coercion(): void
    {
        $user = User::query()->firstOrFail();
        $synonym = SearchSynonym::query()->create([
            'from_term' => 'cotton',
            'to_term' => 'ผ้าฝ้าย',
        ]);

        $this->actingAs($user)
            ->post(route('admin.catalog.search-synonyms.store'), [
                'from_term' => ['cotton'],
                'to_term' => ['fabric'],
            ])
            ->assertInvalid(['from_term', 'to_term']);

        $this->actingAs($user)
            ->put(route('admin.catalog.search-synonyms.update', $synonym->id), [
                'from_term' => ['linen'],
                'to_term' => ['fabric'],
            ])
            ->assertInvalid(['from_term', 'to_term']);

        $this->assertSame('cotton', $synonym->fresh()->from_term);
        $this->assertSame('ผ้าฝ้าย', $synonym->to_term);
        $this->assertSame(1, SearchSynonym::query()->count());
    }
}
